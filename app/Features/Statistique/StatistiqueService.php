<?php

namespace App\Features\Statistique;

use App\Features\AnneeUniversitaire\AnneeUniversitaireService;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StatistiqueService {
    //==================================================================================================================
    // Durée d'une séance en heures, calculée par MySQL : "08:30" → "10:30" = 2.0
    //==================================================================================================================
    const string duree_sql = 'TIME_TO_SEC(TIMEDIFF(seances.heure_fin, seances.heure_debut)) / 3600';


    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    //
    // FILTRES
    //
    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    //==================================================================================================================
    // Par défaut : l'année universitaire active (sinon les 12 derniers mois)
    //==================================================================================================================
    public static function get_filtres_par_defaut() : array {
        $annee = AnneeUniversitaireService::get_active();

        return [
            'date_debut' => $annee?->date_debut?->format('Y-m-d') ?? today()
                    ->subYear()
                    ->format('Y-m-d'),
            'date_fin'   => $annee?->date_fin?->format('Y-m-d') ?? today()->format('Y-m-d'),
            'cycle_id'        => null,
            'niveau_etude_id' => null,
            'groupe_id'  => null,
            'module_id'  => null,
        ];
    }



    //==================================================================================================================
    // Séances tenues : non annulées, appel fait, dans la période et les filtres
    //==================================================================================================================
    protected static function base_seances(array $filtres) : Builder {
        return DB
            ::table('seances')
            ->join('groupes', 'groupes.id', '=', 'seances.groupe_id')
            ->join('niveaux_etudes', 'niveaux_etudes.id', '=', 'groupes.niveau_etude_id')
            ->whereNull('seances.deleted_at')
            ->where('seances.annulee', false)
            ->whereNotNull('seances.appel_fait_le')
            ->whereBetween('seances.date', [$filtres['date_debut'], $filtres['date_fin']])
            ->when($filtres['groupe_id'] ?? null, fn(Builder $query, $id) => $query->where('seances.groupe_id', $id))
            ->when($filtres['module_id'] ?? null, fn(Builder $query, $id) => $query->where('seances.module_id', $id))
            ->when($filtres['niveau_etude_id'] ?? null, fn(Builder $query, $id) => $query->where('groupes.niveau_etude_id', $id))
            ->when($filtres['cycle_id'] ?? null, fn(Builder $query, $id) => $query->where('niveaux_etudes.cycle_id', $id))
            ->when($filtres['enseignant_id'] ?? null, fn(Builder $query, $id) => $query->where('seances.enseignant_id', $id));
    }



    //==================================================================================================================
    // Absences de ces séances (hors absences archivées)
    //==================================================================================================================
    protected static function base_absences(array $filtres) : Builder {
        return self
            ::base_seances($filtres)
            ->join('absences', 'absences.seance_id', '=', 'seances.id')
            ->whereNull('absences.deleted_at');
    }



    //==================================================================================================================
    // Effectif actuel de chaque groupe (sous-requête)
    //==================================================================================================================
    protected static function sous_requete_effectifs() : Builder {
        return DB
            ::table('inscriptions')
            ->join('etudiants', 'etudiants.id', '=', 'inscriptions.etudiant_id')
            ->whereNull('inscriptions.deleted_at')
            ->whereNull('etudiants.deleted_at')
            ->selectRaw('inscriptions.groupe_id, COUNT(*) as effectif')
            ->groupBy('inscriptions.groupe_id');
    }



    public static function taux(float $heures_absence, float $heures_prevues) : float {
        return $heures_prevues > 0 ? round($heures_absence / $heures_prevues * 100, 1) : 0.0;
    }



    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    //
    // INDICATEURS (BF-24)
    //
    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    public static function get_resume(array $filtres) : array {
        $duree = self::duree_sql;

        $prevu = self
            ::base_seances($filtres)
            ->leftJoinSub(self::sous_requete_effectifs(), 'eff', 'eff.groupe_id', '=', 'seances.groupe_id')
            ->selectRaw("COUNT(*) as nb_seances, COALESCE(SUM(($duree) * COALESCE(eff.effectif, 0)), 0) as heures_prevues")
            ->first();

        $absences = self
            ::base_absences($filtres)
            ->selectRaw("COUNT(*) as nb_absences, COALESCE(SUM(absences.justifiee), 0) as nb_justifiees, COALESCE(SUM($duree), 0) as heures_absence")
            ->first();

        $nb_absences = (int) ($absences->nb_absences ?? 0);

        return [
            'nb_seances'        => (int) ($prevu->nb_seances ?? 0),
            'nb_absences'       => $nb_absences,
            'nb_justifiees'     => (int) ($absences->nb_justifiees ?? 0),
            'nb_non_justifiees' => $nb_absences - (int) ($absences->nb_justifiees ?? 0),
            'heures_absence'    => round((float) ($absences->heures_absence ?? 0), 1),
            'taux'              => self::taux((float) ($absences->heures_absence ?? 0), (float) ($prevu->heures_prevues ?? 0)),
        ];
    }



    //==================================================================================================================
    // Par groupe : taux d'absence de chaque groupe
    //==================================================================================================================
    public static function get_par_groupe(array $filtres) : array {
        $duree = self::duree_sql;

        $absences = self
            ::base_absences($filtres)
            ->groupBy('seances.groupe_id')
            ->selectRaw("seances.groupe_id, COUNT(*) as nb_absences, SUM($duree) as heures_absence")
            ->get()
            ->keyBy('groupe_id');

        return self
            ::base_seances($filtres)
            ->leftJoinSub(self::sous_requete_effectifs(), 'eff', 'eff.groupe_id', '=', 'seances.groupe_id')
            ->groupBy('groupes.id', 'groupes.nom')
            ->selectRaw("groupes.id, groupes.nom, SUM(($duree) * COALESCE(eff.effectif, 0)) as heures_prevues")
            ->get()
            ->map(fn($ligne) => [
                'nom'            => $ligne->nom,
                'nb_absences'    => (int) ($absences[$ligne->id]->nb_absences ?? 0),
                'heures_absence' => round((float) ($absences[$ligne->id]->heures_absence ?? 0), 1),
                'taux'           => self::taux((float) ($absences[$ligne->id]->heures_absence ?? 0), (float) $ligne->heures_prevues),
            ])
            ->sortByDesc('taux')
            ->values()
            ->all();
    }



    //==================================================================================================================
    // Par module : les modules les plus touchés (BF-25)
    //==================================================================================================================
    public static function get_par_module(array $filtres) : array {
        $duree = self::duree_sql;

        $absences = self
            ::base_absences($filtres)
            ->groupBy('seances.module_id')
            ->selectRaw("seances.module_id, COUNT(*) as nb_absences, SUM($duree) as heures_absence")
            ->get()
            ->keyBy('module_id');

        return self
            ::base_seances($filtres)
            ->join('modules', 'modules.id', '=', 'seances.module_id')
            ->leftJoinSub(self::sous_requete_effectifs(), 'eff', 'eff.groupe_id', '=', 'seances.groupe_id')
            ->groupBy('modules.id', 'modules.code', 'modules.intitule')
            ->selectRaw("modules.id, modules.code, modules.intitule, COUNT(DISTINCT seances.id) as nb_seances, SUM(($duree) * COALESCE(eff.effectif, 0)) as heures_prevues")
            ->get()
            ->map(fn($ligne) => [
                'module'         => "{$ligne->code} — {$ligne->intitule}",
                'nb_seances'     => (int) $ligne->nb_seances,
                'nb_absences'    => (int) ($absences[$ligne->id]->nb_absences ?? 0),
                'heures_absence' => round((float) ($absences[$ligne->id]->heures_absence ?? 0), 1),
                'taux'           => self::taux((float) ($absences[$ligne->id]->heures_absence ?? 0), (float) $ligne->heures_prevues),
            ])
            ->sortByDesc('taux')
            ->values()
            ->all();
    }



    //==================================================================================================================
    // Classement des étudiants les plus absents (BF-25)
    //==================================================================================================================
    public static function get_top_etudiants(array $filtres, int $limite = 10) : array {
        $duree = self::duree_sql;

        //==============================================================================================================
        // Heures de séances tenues pour un étudiant de chaque groupe
        //==============================================================================================================
        $heures_par_groupe = self
            ::base_seances($filtres)
            ->groupBy('seances.groupe_id')
            ->selectRaw("seances.groupe_id, SUM($duree) as heures")
            ->pluck('heures', 'groupe_id');

        return self
            ::base_absences($filtres)
            ->join('etudiants', 'etudiants.id', '=', 'absences.etudiant_id')
            ->groupBy('etudiants.id', 'etudiants.cle', 'etudiants.cne', 'etudiants.nom', 'etudiants.prenom')
            ->selectRaw("
                etudiants.cle, etudiants.cne, etudiants.nom, etudiants.prenom,
                MAX(groupes.nom) as groupe, MAX(seances.groupe_id) as groupe_id,
                COUNT(*) as nb_absences,
                SUM(1 - absences.justifiee) as nb_non_justifiees,
                SUM($duree) as heures_absence
            ")
            ->orderByDesc('heures_absence')
            ->limit($limite)
            ->get()
            ->map(fn($ligne) => [
                'cle'               => $ligne->cle,
                'cne'               => $ligne->cne,
                'nom_complet'       => trim("{$ligne->prenom} " . mb_strtoupper($ligne->nom)),
                'groupe'            => $ligne->groupe,
                'nb_absences'       => (int) $ligne->nb_absences,
                'nb_non_justifiees' => (int) $ligne->nb_non_justifiees,
                'heures_absence'    => round((float) $ligne->heures_absence, 1),
                'taux'              => self::taux((float) $ligne->heures_absence, (float) ($heures_par_groupe[$ligne->groupe_id] ?? 0)),
            ])
            ->all();
    }



    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    //
    // ÉVOLUTION DANS LE TEMPS (BF-26)
    //
    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    //==================================================================================================================
    // Absences par jour, en complétant les jours sans absence par 0
    //==================================================================================================================
    public static function get_evolution_par_jour(array $filtres) : array {
        $totaux = self
            ::base_absences($filtres)
            ->groupBy('seances.date')
            ->selectRaw('seances.date as jour, COUNT(*) as total')
            ->pluck('total', 'jour');

        $labels  = [];
        $valeurs = [];

        for ($jour = Carbon::parse($filtres['date_debut']); $jour->lte(Carbon::parse($filtres['date_fin'])); $jour = $jour->addDay()) {
            $labels[]  = $jour->format('d/m');
            $valeurs[] = (int) ($totaux[$jour->format('Y-m-d')] ?? 0);
        }

        return ['labels' => $labels, 'valeurs' => $valeurs];
    }



    //==================================================================================================================
    // Absences par semaine (lundi de chaque semaine)
    //==================================================================================================================
    public static function get_evolution_par_semaine(array $filtres) : array {
        $lignes = self
            ::base_absences($filtres)
            ->groupByRaw('YEARWEEK(seances.date, 3)')
            ->selectRaw('YEARWEEK(seances.date, 3) as semaine, MIN(seances.date) as premier_jour, COUNT(*) as total')
            ->orderBy('semaine')
            ->get();

        return [
            'labels'  => $lignes->map(fn($ligne) => 'Sem. du ' . Carbon::parse($ligne->premier_jour)
                                                                       ->startOfWeek()
                                                                       ->format('d/m'))
                                ->all(),
            'valeurs' => $lignes->map(fn($ligne) => (int) $ligne->total)
                                ->all(),
        ];
    }
}
