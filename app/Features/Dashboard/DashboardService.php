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
        $annee = AnneeUniversitaireService::get_active();

        return [
            'annee'                 => $annee?->libelle,
            'absences_aujourdhui'   => Absence::whereHas('seance', fn(Builder $query) => $query->whereDate('date', today()))
                                              ->count(),
            'absences_semaine'      => Absence::whereHas('seance', fn(Builder $query) => $query->whereBetween('date', [today()
                                                                                                                           ->startOfWeek()
                                                                                                                           ->format('Y-m-d'), today()->format('Y-m-d')]))
                                              ->count(),
            'justificatifs_attente' => Justificatif::where('statut', JustificatifService::statut_en_attente)
                                                   ->count(),
            'appels_manquants'      => Seance
                ::query()
                ->non_annulees()
                ->whereNull('appel_fait_le')
                ->whereDate('date', '<=', today())
                ->when($annee, fn(Builder $query) => $query->whereBetween('date', [$annee->date_debut?->format('Y-m-d'), $annee->date_fin?->format('Y-m-d')]))
                ->count(),
            'taux_annee'            => StatistiqueService::get_resume(StatistiqueService::get_filtres_par_defaut())['taux'],
            'evolution_30_jours'    => StatistiqueService::get_evolution_par_jour([
                                                                                      ...StatistiqueService::get_filtres_par_defaut(),
                                                                                      'date_debut' => today()
                                                                                          ->subDays(29)
                                                                                          ->format('Y-m-d'),
                                                                                      'date_fin'   => today()->format('Y-m-d'),
                                                                                  ]),
            'top_etudiants'         => StatistiqueService::get_top_etudiants(StatistiqueService::get_filtres_par_defaut(), 5),
        ];
    }



    //==================================================================================================================
    // Enseignant (BF-29)
    //==================================================================================================================
    public static function get_donnees_enseignant(Enseignant $enseignant) : array {
        $vers_ligne = fn(Seance $seance) => [
            'date'       => $seance->date?->format('d/m/Y'),
            'horaire'    => $seance->horaire_render,
            'module'     => $seance->module->code,
            'groupe'     => $seance->groupe->nom,
            'salle'      => $seance->salle,
            'annulee'    => $seance->annulee,
            'appel_fait' => $seance->appel_fait,
            'url_appel'  => route('appel.detail', ['cle' => $seance->cle]),
        ];

        $seances = Seance::query()
                         ->where('enseignant_id', $enseignant->id)
                         ->with(['module', 'groupe']);

        return [
            'seances_du_jour'  => (clone $seances)
                ->whereDate('date', today())
                ->orderBy('heure_debut')
                ->get()
                ->map($vers_ligne)
                ->all(),
            'appels_en_retard' => (clone $seances)
                ->non_annulees()
                ->whereNull('appel_fait_le')
                ->whereDate('date', '<', today())
                ->orderByDesc('date')
                ->limit(10)
                ->get()
                ->map($vers_ligne)
                ->all(),
            'seances_a_venir'  => (clone $seances)
                ->non_annulees()
                ->whereBetween('date', [today()
                                            ->addDay()
                                            ->format('Y-m-d'), today()
                                            ->addDays(7)
                                            ->format('Y-m-d')])
                ->count(),
        ];
    }
}
