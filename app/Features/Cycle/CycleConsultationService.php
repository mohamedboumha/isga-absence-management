<?php

namespace App\Features\Cycle;

use App\Features\AnneeUniversitaire\AnneeUniversitaireService;
use App\Features\Filiere\Filiere;
use App\Features\NiveauEtude\NiveauEtude;
use App\Features\NiveauEtude\NiveauEtudeResumeService;

class CycleConsultationService {
    //==================================================================================================================
    // Tout ce que la fiche du cycle affiche en consultation : sa structure, ses filières, ses chiffres de l'année
    //==================================================================================================================
    public static function get(Cycle $cycle) : array {
        $niveaux = NiveauEtude
            ::query()
            ->where('cycle_id', $cycle->id)
            ->with(['cycle', 'filiere.cycle'])
            ->get();

        $niveaux_resumes = NiveauEtudeResumeService::get_niveaux($niveaux);

        $filieres = Filiere
            ::query()
            ->where('cycle_id', $cycle->id)
            ->with('cycle')
            ->withCount('niveaux')
            ->orderBy('code')
            ->get();

        return [
            'resume'    => [
                'annee'       => AnneeUniversitaireService::get_active()?->libelle,
                'nb_annees'   => (int) $cycle->nb_annees,
                'nb_filieres' => $filieres->count(),
                'nb_niveaux'  => $niveaux->count(),
                'nb_groupes'  => (int) collect($niveaux_resumes)->sum('nb_groupes'),
                'effectif'    => (int) collect($niveaux_resumes)->sum('effectif'),
            ],
            'structure' => NiveauEtudeResumeService::par_annee($niveaux_resumes, (int) $cycle->nb_annees),
            'filieres'  => $filieres->map(fn(Filiere $filiere) => [
                'code'       => $filiere->code,
                'nom'        => $filiere->nom,
                'couleur'    => $filiere->couleur_effective,
                'nb_niveaux' => $filiere->niveaux_count,
                'url'        => route('filiere.detail', ['cle' => $filiere->cle]),
            ])
                                    ->all(),
            'liens'     => [
                'groupes'   => route('groupes.list', ['filtres' => ['cycle' => $cycle->id]]),
                'etudiants' => route('etudiants.list', ['filtres' => ['cycle' => $cycle->id]]),
            ],
        ];
    }
}
