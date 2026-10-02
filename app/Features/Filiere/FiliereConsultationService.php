<?php

namespace App\Features\Filiere;

use App\Features\AnneeUniversitaire\AnneeUniversitaireService;
use App\Features\NiveauEtude\NiveauEtude;
use App\Features\NiveauEtude\NiveauEtudeResumeService;
use App\Features\Statistique\StatistiqueService;

class FiliereConsultationService {
    //==================================================================================================================
    // Tout ce que la fiche de la filière affiche en consultation : ses niveaux, ses groupes, ses chiffres de l'année
    //==================================================================================================================
    public static function get(Filiere $filiere) : array {
        $filiere->loadMissing('cycle');

        $niveaux = NiveauEtude
            ::query()
            ->where('filiere_id', $filiere->id)
            ->with(['cycle', 'filiere.cycle'])
            ->get();

        $niveaux_resumes = NiveauEtudeResumeService::get_niveaux($niveaux);


        //==============================================================================================================
        // Taux d'absence de la filière : mêmes calculs que la page Statistiques (RG-08)
        //==============================================================================================================
        $resume = StatistiqueService::get_resume([
                                                     ...StatistiqueService::get_filtres_par_defaut(),
                                                     'filiere_id' => $filiere->id,
                                                 ]);

        $annees_utilisees = collect($niveaux_resumes)
            ->pluck('annee_cycle')
            ->unique()
            ->sort()
            ->values();

        return [
            'resume'    => [
                'annee'       => AnneeUniversitaireService::get_active()?->libelle,
                'cycle'       => [
                    'code'    => $filiere->cycle->code,
                    'nom'     => $filiere->cycle->nom,
                    'couleur' => $filiere->cycle->couleur,
                    'url'     => route('cycle.detail', ['cle' => $filiere->cycle->cle]),
                ],
                'nb_niveaux'  => $niveaux->count(),
                'nb_groupes'  => (int) collect($niveaux_resumes)->sum('nb_groupes'),
                'effectif'    => (int) collect($niveaux_resumes)->sum('effectif'),
                'nb_absences' => $resume['nb_absences'],
                'taux'        => $resume['taux'],
            ],

            //==========================================================================================================
            // Seulement les années du cycle où la filière a des niveaux (ex. 3ème à 5ème année pour ISI)
            //==========================================================================================================
            'structure' => array_values(array_filter(
                                            NiveauEtudeResumeService::par_annee($niveaux_resumes, (int) $filiere->cycle->nb_annees),
                                            fn(array $annee) => $annees_utilisees->contains($annee['annee'])
                                        )),

            'groupes' => collect($niveaux_resumes)
                ->flatMap(fn(array $niveau) => $niveau['groupes'])
                ->values()
                ->all(),
        ];
    }
}
