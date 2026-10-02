<?php

namespace App\Features\Module;

use App\Features\AnneeUniversitaire\AnneeUniversitaireService;
use App\Features\Enseignant\Enseignant;
use App\Features\Groupe\Groupe;
use App\Features\Seance\Seance;
use App\Features\Statistique\StatistiqueService;

class ModuleConsultationService {
    //==================================================================================================================
    // Tout ce que la fiche du module affiche en consultation (sur l'année active)
    //==================================================================================================================
    public static function get(Module $module) : array {
        $module->loadMissing(['niveau_etude.cycle', 'niveau_etude.filiere.cycle']);

        $filtres = [
            ...StatistiqueService::get_filtres_par_defaut(),
            'module_id' => $module->id,
        ];

        $resume = StatistiqueService::get_resume($filtres);


        //==============================================================================================================
        // Séances tenues du module dans l'année (non annulées, appel fait), regroupées par groupe
        //==============================================================================================================
        $seances_tenues = Seance
            ::query()
            ->where('module_id', $module->id)
            ->non_annulees()
            ->whereNotNull('appel_fait_le')
            ->whereBetween('date', [$filtres['date_debut'], $filtres['date_fin']])
            ->get();

        $heures_par_groupe = $seances_tenues
            ->groupBy('groupe_id')
            ->map(fn($seances) => round($seances->sum(fn(Seance $seance) => $seance->duree_heures), 1));


        //==============================================================================================================
        // Avancement : chaque groupe de l'année du niveau du module, heures faites / volume horaire
        //==============================================================================================================
        $annee   = AnneeUniversitaireService::get_active();
        $groupes = $annee
            ? Groupe::query()
                    ->where('annee_universitaire_id', $annee->id)
                    ->where('niveau_etude_id', $module->niveau_etude_id)
                    ->orderBy('nom')
                    ->get()
            : collect();

        $volume = max((int) $module->volume_horaire, 1);

        $avancement = $groupes->map(fn(Groupe $groupe) => [
            'nom'         => $groupe->nom,
            'heures'      => $heures_par_groupe->get($groupe->id, 0),
            'pourcentage' => min(round($heures_par_groupe->get($groupe->id, 0) / $volume * 100), 100),
            'url'         => route('groupe.detail', ['cle' => $groupe->cle]),
        ])
                              ->values()
                              ->all();

        $heures_moyennes = $groupes->isNotEmpty()
            ? round(collect($avancement)->avg('heures'), 1)
            : 0;


        //==============================================================================================================
        // Séances : 5 prochaines et 5 dernières
        //==============================================================================================================
        $seances = fn() => Seance
            ::query()
            ->where('module_id', $module->id)
            ->with(['module.niveau_etude.cycle', 'module.niveau_etude.filiere.cycle', 'groupe']);

        $a_venir = $seances()
            ->whereDate('date', '>=', today())
            ->where('annulee', false)
            ->orderBy('date')
            ->orderBy('heure_debut')
            ->limit(5)
            ->get();

        $recentes = $seances()
            ->whereDate('date', '<', today())
            ->orderByDesc('date')
            ->orderByDesc('heure_debut')
            ->limit(5)
            ->get();

        return [
            'resume'      => [
                'niveau_code'       => $module->niveau_etude->code,
                'niveau_libelle'    => $module->niveau_etude->libelle,
                'couleur'           => $module->niveau_etude->couleur_effective,
                'volume'            => (int) $module->volume_horaire,
                'heures_moyennes'   => $heures_moyennes,
                'avancement'        => min(round($heures_moyennes / $volume * 100), 100),
                'nb_seances'        => $resume['nb_seances'],
                'nb_absences'       => $resume['nb_absences'],
                'nb_non_justifiees' => $resume['nb_non_justifiees'],
                'taux'              => $resume['taux'],
            ],
            'avancement'  => $avancement,
            'enseignants' => $module
                ->enseignants()
                ->orderBy('nom')
                ->get()
                ->map(fn(Enseignant $enseignant) => [
                    'nom_complet' => $enseignant->nom_complet,
                    'email'       => $enseignant->email,
                    'url'         => route('enseignant.detail', ['cle' => $enseignant->cle]),
                ])
                ->all(),
            'a_venir'     => $a_venir->map(fn(Seance $seance) => self::seance_to_array($seance))
                                     ->all(),
            'recentes'    => $recentes->map(fn(Seance $seance) => self::seance_to_array($seance))
                                      ->all(),
            'liens'       => [
                'niveau' => route('niveau-etude.detail', ['cle' => $module->niveau_etude->cle]),
            ],
        ];
    }



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
            'url'      => route('seance.detail', ['cle' => $seance->cle]),
        ];
    }
}
