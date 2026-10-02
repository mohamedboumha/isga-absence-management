<?php

namespace App\Features\Enseignant;

use App\Features\Groupe\Groupe;
use App\Features\Module\Module;
use App\Features\Seance\Seance;
use App\Features\Statistique\StatistiqueService;
use Illuminate\Database\Eloquent\Builder;

class EnseignantConsultationService {
    //==================================================================================================================
    // Tout ce que la fiche de l'enseignant affiche en consultation (sur l'année active)
    //==================================================================================================================
    public static function get(Enseignant $enseignant) : array {
        $filtres = [
            ...StatistiqueService::get_filtres_par_defaut(),
            'enseignant_id' => $enseignant->id,
        ];


        //==============================================================================================================
        // Indicateurs : mêmes calculs que la page Statistiques (RG-08), limités à ses séances
        //==============================================================================================================
        $resume = StatistiqueService::get_resume($filtres);

        $heures_enseignees = Seance
            ::query()
            ->where('enseignant_id', $enseignant->id)
            ->non_annulees()
            ->whereNotNull('appel_fait_le')
            ->whereBetween('date', [$filtres['date_debut'], $filtres['date_fin']])
            ->get()
            ->sum(fn(Seance $seance) => $seance->duree_heures);


        //==============================================================================================================
        // Séances : appels à faire (passés, sans appel) et prochaines séances
        //==============================================================================================================
        $seances = fn() => Seance
            ::query()
            ->where('enseignant_id', $enseignant->id)
            ->where('annulee', false)
            ->with(['module.niveau_etude.cycle', 'module.niveau_etude.filiere.cycle', 'groupe']);

        $appels_a_faire = $seances()
            ->whereNull('appel_fait_le')
            ->whereDate('date', '<=', today());

        $nb_appels_a_faire = (clone $appels_a_faire)->count();

        $appels_a_faire = $appels_a_faire
            ->orderByDesc('date')
            ->orderByDesc('heure_debut')
            ->limit(5)
            ->get();

        $a_venir = $seances()
            ->whereDate('date', '>', today())
            ->orderBy('date')
            ->orderBy('heure_debut')
            ->limit(5)
            ->get();


        //==============================================================================================================
        // Modules enseignés et groupes de l'année
        //==============================================================================================================
        $modules = $enseignant
            ->modules()
            ->with(['niveau_etude.cycle', 'niveau_etude.filiere.cycle'])
            ->orderBy('code')
            ->get();

        $groupes = Groupe
            ::query()
            ->whereHas('seances', fn(Builder $query) => $query
                ->where('enseignant_id', $enseignant->id)
                ->whereBetween('date', [$filtres['date_debut'], $filtres['date_fin']]))
            ->with(['niveau_etude.cycle', 'niveau_etude.filiere.cycle'])
            ->orderBy('nom')
            ->get();

        return [
            'resume'         => [
                'nb_seances'        => $resume['nb_seances'],
                'heures_enseignees' => round($heures_enseignees, 1),
                'nb_appels_a_faire' => $nb_appels_a_faire,
                'nb_absences'       => $resume['nb_absences'],
                'nb_non_justifiees' => $resume['nb_non_justifiees'],
                'taux'              => $resume['taux'],
            ],
            'appels_a_faire' => $appels_a_faire->map(fn(Seance $seance) => self::seance_to_array($seance))->all(),
            'a_venir'        => $a_venir->map(fn(Seance $seance) => self::seance_to_array($seance))->all(),
            'modules'        => $modules->map(fn(Module $module) => [
                'code'     => $module->code,
                'intitule' => $module->intitule,
                'niveau'   => $module->niveau_etude->code,
                'couleur'  => $module->niveau_etude->couleur_effective,
                'url'      => route('module.detail', ['cle' => $module->cle]),
            ])->all(),
            'groupes'        => $groupes->map(fn(Groupe $groupe) => [
                'nom'     => $groupe->nom,
                'niveau'  => $groupe->niveau_etude->libelle,
                'couleur' => $groupe->niveau_etude->couleur_effective,
                'url'     => route('groupe.detail', ['cle' => $groupe->cle]),
            ])->all(),
            'liens'          => [
                'seances'        => route('seances.list', ['filtres' => ['enseignant' => $enseignant->id]]),
                'appels_a_faire' => route('seances.list', ['filtres' => ['enseignant' => $enseignant->id, 'statut' => 'appel_a_faire']]),
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
