<?php

namespace App\Features\NiveauEtude;

use App\Features\AnneeUniversitaire\AnneeUniversitaireService;
use App\Features\Groupe\Groupe;
use App\Features\Module\Module;

class NiveauEtudeConsultationService {
    //==================================================================================================================
    // Tout ce que la fiche du niveau affiche en consultation : groupes de l'année, modules, parcours
    //==================================================================================================================
    public static function get(NiveauEtude $niveau) : array {
        $niveau->loadMissing(['cycle', 'filiere.cycle', 'suivants.cycle', 'suivants.filiere.cycle', 'precedents.cycle', 'precedents.filiere.cycle']);


        //==============================================================================================================
        // Groupes de l'année active, avec leur effectif
        //==============================================================================================================
        $annee   = AnneeUniversitaireService::get_active();
        $groupes = $annee
            ? Groupe::query()
                    ->where('annee_universitaire_id', $annee->id)
                    ->where('niveau_etude_id', $niveau->id)
                    ->withCount('inscriptions')
                    ->orderBy('nom')
                    ->get()
            : collect();


        //==============================================================================================================
        // Modules, regroupés par semestre (1 .. nb_semestres)
        //==============================================================================================================
        $modules = Module
            ::query()
            ->where('niveau_etude_id', $niveau->id)
            ->orderBy('semestre')
            ->orderBy('code')
            ->get();

        $semestres = collect(range(1, max((int) $niveau->nb_semestres, 1)))
            ->map(function (int $numero) use ($modules, $niveau) {
                $du_semestre = $modules->where('semestre', $numero)
                                       ->values();

                return [
                    'numero'  => $numero,
                    'volume'  => (int) $du_semestre->sum('volume_horaire'),
                    'modules' => $du_semestre->map(fn(Module $module) => [
                        'code'     => $module->code,
                        'intitule' => $module->intitule,
                        'volume'   => (int) $module->volume_horaire,
                        'couleur'  => $niveau->couleur_effective,
                        'url'      => route('module.detail', ['cle' => $module->cle]),
                    ])
                                             ->all(),
                ];
            })
            ->all();

        $vers_badge = fn(NiveauEtude $autre) => [
            'code'    => $autre->code,
            'libelle' => $autre->libelle,
            'couleur' => $autre->couleur_effective,
            'url'     => route('niveau-etude.detail', ['cle' => $autre->cle]),
        ];

        return [
            'resume'    => [
                'cycle'        => $niveau->cycle->nom,
                'annee'        => $annee?->libelle,
                'nb_groupes'   => $groupes->count(),
                'effectif'     => (int) $groupes->sum('inscriptions_count'),
                'nb_modules'   => $modules->count(),
                'volume_total' => (int) $modules->sum('volume_horaire'),
            ],
            'groupes'   => $groupes->map(fn(Groupe $groupe) => [
                'nom'      => $groupe->nom,
                'effectif' => $groupe->inscriptions_count,
                'url'      => route('groupe.detail', ['cle' => $groupe->cle]),
            ])
                                   ->all(),
            'semestres' => $semestres,
            'parcours'  => [
                'precedents'         => $niveau->precedents->map($vers_badge)
                                                           ->values()
                                                           ->all(),
                'suivants'           => $niveau->suivants->map($vers_badge)
                                                         ->values()
                                                         ->all(),
                'est_derniere_annee' => $niveau->est_derniere_annee,
            ],
            'liens'     => [
                'groupes' => route('groupes.list', ['filtres' => ['niveau' => $niveau->id]]),
                'modules' => route('modules.list', ['filtres' => ['niveau' => $niveau->id]]),
            ],
        ];
    }
}
