<?php

namespace App\Features\NiveauEtude;

use App\Features\AnneeUniversitaire\AnneeUniversitaireService;
use App\Features\Groupe\Groupe;
use Illuminate\Support\Collection;

class NiveauEtudeResumeService {
    //==================================================================================================================
    // Des niveaux, avec leurs groupes et leur effectif de l'année active
    //==================================================================================================================
    public static function get_niveaux(Collection $niveaux) : array {
        $annee = AnneeUniversitaireService::get_active();

        $groupes = $annee && $niveaux->isNotEmpty()
            ? Groupe
                ::query()
                ->where('annee_universitaire_id', $annee->id)
                ->whereIn('niveau_etude_id', $niveaux->modelKeys())
                ->withCount('inscriptions')
                ->orderBy('nom')
                ->get()
                ->groupBy('niveau_etude_id')
            : collect();

        return $niveaux
            ->sortBy(fn(NiveauEtude $niveau) => [$niveau->annee_cycle, $niveau->code])
            ->values()
            ->map(function (NiveauEtude $niveau) use ($groupes) {
                $groupes_du_niveau = $groupes->get($niveau->id, collect());

                return [
                    'code'        => $niveau->code,
                    'libelle'     => $niveau->libelle,
                    'couleur'     => $niveau->couleur_effective,
                    'annee_cycle' => (int) $niveau->annee_cycle,
                    'nb_groupes'  => $groupes_du_niveau->count(),
                    'effectif'    => (int) $groupes_du_niveau->sum('inscriptions_count'),
                    'groupes'     => $groupes_du_niveau->map(fn(Groupe $groupe) => [
                        'nom'      => $groupe->nom,
                        'effectif' => $groupe->inscriptions_count,
                        'couleur'  => $niveau->couleur_effective,
                        'url'      => route('groupe.detail', ['cle' => $groupe->cle]),
                    ])
                                                       ->values()
                                                       ->all(),
                    'url'         => route('niveau-etude.detail', ['cle' => $niveau->cle]),
                ];
            })
            ->all();
    }



    //==================================================================================================================
    // Les mêmes niveaux, rangés par année du cycle : [ ['annee' => 1, 'label' => '1ère année', 'niveaux' => [...]], ... ]
    //==================================================================================================================
    public static function par_annee(array $niveaux, int $nb_annees) : array {
        return collect(range(1, max($nb_annees, 1)))
            ->map(fn(int $annee) => [
                'annee'   => $annee,
                'label'   => $annee === 1 ? "1ère année" : "{$annee}ème année",
                'niveaux' => array_values(array_filter($niveaux, fn(array $niveau) => $niveau['annee_cycle'] === $annee)),
            ])
            ->all();
    }
}
