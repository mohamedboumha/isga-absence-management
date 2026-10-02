<?php

namespace App\Features\Dashboard;

use App\Features\Absence\Absence;
use App\Features\AnneeUniversitaire\AnneeUniversitaireService;
use App\Features\Enseignant\Enseignant;
use App\Features\Justificatif\Justificatif;
use App\Features\Justificatif\JustificatifService;
use App\Features\Seance\Seance;
use App\Features\Statistique\StatistiqueService;
use Illuminate\Database\Eloquent\Builder;

class DashboardService {
    //==================================================================================================================
    // Administration (BF-23)
    //==================================================================================================================
    public static function get_donnees_administration() : array {
        $annee  = AnneeUniversitaireService::get_active();
        $defaut = StatistiqueService::get_filtres_par_defaut();


        //==============================================================================================================
        // Absences : cette semaine (lundi → aujourd'hui) et la même période la semaine dernière
        //==============================================================================================================
        $compter_absences = fn(string $du, string $au) => Absence
            ::whereHas('seance', fn(Builder $query) => $query->whereBetween('date', [$du, $au]))
            ->count();

        $lundi = today()->startOfWeek();


        //==============================================================================================================
        // Taux sur 30 jours, comparé aux 30 jours précédents
        //==============================================================================================================
        $filtres_30_jours = [...$defaut, 'date_debut' => today()
            ->subDays(29)
            ->format('Y-m-d'), 'date_fin'             => today()->format('Y-m-d')];

        $resume_30_jours  = StatistiqueService::get_resume($filtres_30_jours);
        $resume_precedent = StatistiqueService::get_resume(StatistiqueService::get_filtres_periode_precedente($filtres_30_jours));


        //==============================================================================================================
        // Séances du jour, dans toute l'école
        //==============================================================================================================
        $seances_du_jour = Seance
            ::query()
            ->whereDate('date', today())
            ->with(['module.niveau_etude.cycle', 'module.niveau_etude.filiere.cycle', 'groupe', 'enseignant'])
            ->orderBy('heure_debut')
            ->get();

        $non_annulees = $seances_du_jour->where('annulee', false);


        //==============================================================================================================
        // À traiter
        //==============================================================================================================
        $justificatifs_attente = Justificatif::query()
                                             ->where('statut', JustificatifService::statut_en_attente)
                                             ->get();

        return [
            'date_du_jour' => ucfirst(today()
                                          ->locale('fr')
                                          ->isoFormat('dddd D MMMM YYYY')),
            'annee'        => $annee?->libelle,

            'absences_aujourdhui'   => $compter_absences(today()->format('Y-m-d'), today()->format('Y-m-d')),
            'absences_semaine'      => $compter_absences($lundi->format('Y-m-d'), today()->format('Y-m-d')),
            'absences_semaine_prec' => $compter_absences($lundi->copy()
                                                               ->subWeek()
                                                               ->format('Y-m-d'), today()
                                                             ->subWeek()
                                                             ->format('Y-m-d')),
            'taux_30_jours'         => $resume_30_jours['taux'],
            'taux_30_jours_prec'    => $resume_precedent['nb_seances'] > 0 ? $resume_precedent['taux'] : null,

            'nb_seances_du_jour'  => $non_annulees->count(),
            'nb_appels_faits'     => $non_annulees->whereNotNull('appel_fait_le')
                                                  ->count(),
            'seances_du_jour'     => $seances_du_jour->take(10)
                                                     ->map(fn(Seance $seance) => [
                                                         'cle'        => $seance->cle,
                                                         'horaire'    => $seance->horaire_render,
                                                         'module'     => $seance->module->code,
                                                         'intitule'   => $seance->module->intitule,
                                                         'couleur'    => $seance->module->niveau_etude->couleur_effective,
                                                         'groupe'     => $seance->groupe->nom,
                                                         'enseignant' => $seance->enseignant->nom_complet,
                                                         'salle'      => $seance->salle,
                                                         'statut'     => $seance->statut_cle,
                                                         'url'        => route('seance.detail', ['cle' => $seance->cle]),
                                                     ])
                                                     ->values()
                                                     ->all(),
            'url_seances_du_jour' => route('seances.list', ['filtres' => ['periode' => ['du' => today()->format('Y-m-d'), 'au' => today()->format('Y-m-d')]]]),

            'a_traiter' => [
                'appels_manquants'         => Seance
                    ::query()
                    ->non_annulees()
                    ->whereNull('appel_fait_le')
                    ->whereDate('date', '<', today())
                    ->when($annee, fn(Builder $query) => $query->whereBetween('date', [$defaut['date_debut'], $defaut['date_fin']]))
                    ->count(),
                'justificatifs_attente'    => $justificatifs_attente->count(),
                'justificatifs_hors_delai' => $justificatifs_attente->filter(fn(Justificatif $justificatif) => $justificatif->hors_delai)
                                                                    ->count(),
            ],

            'evolution_30_jours' => StatistiqueService::get_evolution_par_jour($filtres_30_jours),
            'top_etudiants'      => StatistiqueService::get_top_etudiants($defaut, 5),
            'groupes'            => array_slice(StatistiqueService::get_par_groupe($defaut), 0, 6),
        ];
    }



    //==================================================================================================================
    // Enseignant (BF-29)
    //==================================================================================================================
    public static function get_donnees_enseignant(Enseignant $enseignant) : array {
        $seances = fn() => Seance
            ::query()
            ->where('enseignant_id', $enseignant->id)
            ->with(['module.niveau_etude.cycle', 'module.niveau_etude.filiere.cycle', 'groupe']);

        $en_retard = $seances()
            ->non_annulees()
            ->whereNull('appel_fait_le')
            ->whereDate('date', '<', today());

        return [
            'date_du_jour'     => ucfirst(today()
                                              ->locale('fr')
                                              ->isoFormat('dddd D MMMM YYYY')),
            'seances_du_jour'  => $seances()
                ->whereDate('date', today())
                ->orderBy('heure_debut')
                ->get()
                ->map(fn(Seance $seance) => [
                    ...self::seance_to_array($seance),
                    'salle'      => $seance->salle,
                    'appel_fait' => $seance->appel_fait,
                    'annulee'    => $seance->annulee,
                ])
                ->all(),
            'nb_appels_retard' => (clone $en_retard)->count(),
            'appels_en_retard' => $en_retard->orderByDesc('date')
                                            ->limit(5)
                                            ->get()
                                            ->map(fn(Seance $seance) => self::seance_to_array($seance))
                                            ->all(),
            'a_venir'          => $seances()
                ->non_annulees()
                ->whereDate('date', '>', today())
                ->orderBy('date')
                ->orderBy('heure_debut')
                ->limit(5)
                ->get()
                ->map(fn(Seance $seance) => self::seance_to_array($seance))
                ->all(),
            'resume'           => StatistiqueService::get_resume([
                                                                     ...StatistiqueService::get_filtres_par_defaut(),
                                                                     'enseignant_id' => $enseignant->id,
                                                                 ]),
        ];
    }



    //==================================================================================================================
    // Une séance de l'enseignant : le lien ouvre directement l'appel
    //==================================================================================================================
    protected static function seance_to_array(Seance $seance) : array {
        return [
            'cle'      => $seance->cle,
            'date'     => $seance->date?->format('d/m/Y'),
            'horaire'  => $seance->horaire_render,
            'type'     => $seance->type,
            'module'   => $seance->module->code,
            'intitule' => $seance->module->intitule,
            'couleur'  => $seance->module->niveau_etude->couleur_effective,
            'groupe'   => $seance->groupe->nom,
            'statut'   => $seance->statut_cle,
            'url'      => route('appel.detail', ['cle' => $seance->cle]),
        ];
    }
}
