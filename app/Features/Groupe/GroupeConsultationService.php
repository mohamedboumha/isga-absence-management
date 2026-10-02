<?php

namespace App\Features\Groupe;

use App\Features\Export\ExportService;
use App\Features\Seance\Seance;

class GroupeConsultationService {
    //==================================================================================================================
    // Tout ce que la fiche du groupe affiche en consultation (sur l'année du groupe)
    //==================================================================================================================
    public static function get(Groupe $groupe) : array {
        $groupe->loadMissing(['annee_universitaire', 'niveau_etude.cycle', 'niveau_etude.filiere.cycle']);

        $annee   = $groupe->annee_universitaire;
        $periode = [
            'date_debut' => $annee->date_debut?->format('Y-m-d'),
            'date_fin'   => $annee->date_fin?->format('Y-m-d'),
        ];


        //==============================================================================================================
        // Mêmes calculs que le rapport PDF du groupe (RG-08)
        //==============================================================================================================
        $rapport = ExportService::get_rapport_groupe($groupe, $periode);

        $cles_par_cne = $groupe->etudiants()
                               ->pluck('etudiants.cle', 'etudiants.cne');

        $etudiants = collect($rapport['lignes'])
            ->sortByDesc('taux')
            ->values()
            ->map(fn(array $ligne) => [
                ...$ligne,
                'url' => isset($cles_par_cne[$ligne['cne']]) ? route('etudiant.detail', ['cle' => $cles_par_cne[$ligne['cne']]]) : null,
            ])
            ->all();


        //==============================================================================================================
        // Les 5 prochaines séances et les 5 dernières
        //==============================================================================================================
        $seances = fn() => Seance
            ::query()
            ->where('groupe_id', $groupe->id)
            ->with(['module.niveau_etude.cycle', 'module.niveau_etude.filiere.cycle', 'enseignant']);

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
            'resume'           => [
                'niveau_code'       => $groupe->niveau_etude->code,
                'niveau_libelle'    => $groupe->niveau_etude->libelle,
                'couleur'           => $groupe->niveau_etude->couleur_effective,
                'cycle'             => $groupe->niveau_etude->cycle->nom,
                'annee'             => $annee->libelle,
                'annee_active'      => (bool) $annee->active,
                'effectif'          => count($rapport['lignes']),
                'nb_seances'        => $rapport['nb_seances'],
                'heures'            => $rapport['heures'],
                'nb_absences'       => $rapport['total']['nb_absences'],
                'nb_non_justifiees' => $rapport['total']['nb_non_justifiees'],
                'taux'              => $rapport['total']['taux'],
            ],
            'etudiants'        => $etudiants,
            'seances_a_venir'  => $a_venir->map(fn(Seance $seance) => self::seance_to_array($seance))
                                          ->all(),
            'seances_recentes' => $recentes->map(fn(Seance $seance) => self::seance_to_array($seance))
                                           ->all(),
            'liens'            => [
                //======================================================================================================
                // Le filtre "Groupe" des étudiants porte sur l'année active
                //======================================================================================================
                'etudiants' => $annee->active ? route('etudiants.list', ['filtres' => ['groupe' => $groupe->id]]) : null,
                'seances'   => route('seances.list', ['filtres' => ['groupe' => $groupe->id]]),
            ],
        ];
    }



    protected static function seance_to_array(Seance $seance) : array {
        return [
            'cle'        => $seance->cle,
            'date'       => $seance->date?->format('d/m/Y'),
            'horaire'    => $seance->horaire_render,
            'type'       => $seance->type,
            'module'     => $seance->module->code,
            'intitule'   => $seance->module->intitule,
            'couleur'    => $seance->module->niveau_etude->couleur_effective,
            'enseignant' => $seance->enseignant->nom_complet,
            'statut'     => $seance->statut_cle,
            'url'        => route('seance.detail', ['cle' => $seance->cle]),
        ];
    }
}
