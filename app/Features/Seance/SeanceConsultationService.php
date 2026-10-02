<?php

namespace App\Features\Seance;

use App\Features\Absence\Absence;

class SeanceConsultationService {
    //==================================================================================================================
    // Tout ce que la fiche de la séance affiche en consultation : l'appel et le contexte
    //==================================================================================================================
    public static function get(Seance $seance) : array {
        $seance->loadMissing([
                                 'module.niveau_etude.cycle',
                                 'module.niveau_etude.filiere.cycle',
                                 'groupe.niveau_etude.cycle',
                                 'groupe.niveau_etude.filiere.cycle',
                                 'enseignant',
                                 'appel_fait_par_user',
                             ]);

        $effectif = $seance->groupe->etudiants()
                                   ->count();


        //==============================================================================================================
        // Absents, par ordre alphabétique
        //==============================================================================================================
        $absences = $seance
            ->absences()
            ->with('etudiant')
            ->get()
            ->sortBy(fn(Absence $absence) => "{$absence->etudiant->nom} {$absence->etudiant->prenom}")
            ->values();

        $appel_fait    = $seance->appel_fait_le !== null;
        $nb_absents    = $absences->count();
        $nb_justifiees = $absences->where('justifiee', true)
                                  ->count();
        $nb_presents   = $appel_fait ? max($effectif - $nb_absents, 0) : null;

        return [
            'resume'   => [
                'statut'         => $seance->statut_cle,
                'appel_fait'     => $appel_fait,
                'appel_fait_le'  => $seance->appel_fait_le?->format('d/m/Y à H:i'),
                'appel_fait_par' => $seance->appel_fait_par_user ? trim("{$seance->appel_fait_par_user->prenom} {$seance->appel_fait_par_user->name}") : null,
                'effectif'       => $effectif,
                'nb_presents'    => $nb_presents,
                'nb_absents'     => $nb_absents,
                'nb_justifiees'  => $nb_justifiees,
                'taux_presence'  => $appel_fait && $effectif > 0 ? round($nb_presents / $effectif * 100, 1) : null,
                'duree'          => $seance->duree_heures,
            ],
            'absents'  => $absences->map(fn(Absence $absence) => [
                'nom_complet' => $absence->etudiant->nom_complet,
                'cne'         => $absence->etudiant->cne,
                'justifiee'   => $absence->justifiee,
                'remarque'    => $absence->remarque,
                'url'         => route('etudiant.detail', ['cle' => $absence->etudiant->cle]),
            ])
                                   ->all(),
            'contexte' => [
                'module'     => [
                    'code'     => $seance->module->code,
                    'intitule' => $seance->module->intitule,
                    'couleur'  => $seance->module->niveau_etude->couleur_effective,
                    'url'      => route('module.detail', ['cle' => $seance->module->cle]),
                ],
                'groupe'     => [
                    'nom'     => $seance->groupe->nom,
                    'niveau'  => $seance->groupe->niveau_etude->libelle,
                    'couleur' => $seance->groupe->niveau_etude->couleur_effective,
                    'url'     => route('groupe.detail', ['cle' => $seance->groupe->cle]),
                ],
                'enseignant' => [
                    'nom_complet' => $seance->enseignant->nom_complet,
                    'email'       => $seance->enseignant->email,
                    'url'         => route('enseignant.detail', ['cle' => $seance->enseignant->cle]),
                ],
            ],
            'liens'    => [
                'appel' => route('appel.detail', ['cle' => $seance->cle]),
            ],
        ];
    }
}
