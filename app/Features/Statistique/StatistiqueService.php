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

    //==================================================================================================================
    // Couleur d'un groupe ou d'un module : celle de la filière, sinon celle du cycle (comme couleur_effective)
    //==================================================================================================================
    const string couleur_sql = "COALESCE(filieres.couleur, cycles.couleur, '#475569')";

    const array jours_semaine = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];


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
            'date_debut'      => $annee?->date_debut?->format('Y-m-d') ?? today()
                    ->subYear()
                    ->format('Y-m-d'),
            'date_fin'        => $annee?->date_fin?->format('Y-m-d') ?? today()->format('Y-m-d'),
            'cycle_id'        => null,
            'niveau_etude_id' => null,
            'groupe_id'       => null,
            'module_id'       => null,
            'enseignant_id'   => null,
        ];
    }



    //==================================================================================================================
    // Même durée, juste avant : 01/10 → 31/10 donne 31/08 → 30/09 (pour comparer)
    //==================================================================================================================
    public static function get_filtres_periode_precedente(array $filtres) : array {
        $debut = Carbon::parse($filtres['date_debut']);
        $fin   = Carbon::parse($filtres['date_fin']);
        $jours = (int) $debut->diffInDays($fin) + 1;

        return [
            ...$filtres,
            'date_debut' => $debut->copy()
                                  ->subDays($jours)
                                  ->format('Y-m-d'),
            'date_fin'   => $debut->copy()
                                  ->subDay()
                                  ->format('Y-m-d'),
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
    // Jointures pour la couleur du niveau (filière, sinon cycle)
    //==================================================================================================================
    protected static function avec_couleur(Builder $query) : Builder {
        return $query
            ->leftJoin('filieres', 'filieres.id', '=', 'niveaux_etudes.filiere_id')
            ->join('cycles', 'cycles.id', '=', 'niveaux_etudes.cycle_id');
    }



    //==================================================================================================================
    // Effectif de chaque groupe, d'après ses inscriptions (donc juste pour toutes les années)
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
    // Par groupe : taux d'absence de chaque groupe, avec sa couleur
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
            ::avec_couleur(self::base_seances($filtres))
            ->leftJoinSub(self::sous_requete_effectifs(), 'eff', 'eff.groupe_id', '=', 'seances.groupe_id')
            ->groupBy('groupes.id', 'groupes.nom', 'groupes.cle')
            ->selectRaw("groupes.id, groupes.nom, groupes.cle, MAX(" . self::couleur_sql . ") as couleur, SUM(($duree) * COALESCE(eff.effectif, 0)) as heures_prevues")
            ->get()
            ->map(fn($ligne) => [
                'cle'            => $ligne->cle,
                'nom'            => $ligne->nom,
                'couleur'        => $ligne->couleur,
                'url'            => route('groupe.detail', ['cle' => $ligne->cle]),
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
            ::avec_couleur(self::base_seances($filtres))
            ->join('modules', 'modules.id', '=', 'seances.module_id')
            ->leftJoinSub(self::sous_requete_effectifs(), 'eff', 'eff.groupe_id', '=', 'seances.groupe_id')
            ->groupBy('modules.id', 'modules.code', 'modules.intitule', 'modules.cle')
            ->selectRaw("modules.id, modules.code, modules.intitule, modules.cle, MAX(" . self::couleur_sql . ") as couleur, COUNT(DISTINCT seances.id) as nb_seances, SUM(($duree) * COALESCE(eff.effectif, 0)) as heures_prevues")
            ->get()
            ->map(fn($ligne) => [
                'cle'            => $ligne->cle,
                'code'           => $ligne->code,
                'intitule'       => $ligne->intitule,
                'module'         => "{$ligne->code} — {$ligne->intitule}",
                'couleur'        => $ligne->couleur,
                'url'            => route('module.detail', ['cle' => $ligne->cle]),
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
    // Par type de séance : Cours, TD, TP
    //==================================================================================================================
    public static function get_par_type(array $filtres) : array {
        $duree = self::duree_sql;

        $absences = self
            ::base_absences($filtres)
            ->groupBy('seances.type')
            ->selectRaw("seances.type, COUNT(*) as nb_absences, SUM($duree) as heures_absence")
            ->get()
            ->keyBy('type');

        return self
            ::base_seances($filtres)
            ->leftJoinSub(self::sous_requete_effectifs(), 'eff', 'eff.groupe_id', '=', 'seances.groupe_id')
            ->groupBy('seances.type')
            ->selectRaw("seances.type, COUNT(*) as nb_seances, SUM(($duree) * COALESCE(eff.effectif, 0)) as heures_prevues")
            ->get()
            ->map(fn($ligne) => [
                'type'        => $ligne->type,
                'nb_seances'  => (int) $ligne->nb_seances,
                'nb_absences' => (int) ($absences[$ligne->type]->nb_absences ?? 0),
                'taux'        => self::taux((float) ($absences[$ligne->type]->heures_absence ?? 0), (float) $ligne->heures_prevues),
            ])
            ->sortByDesc('taux')
            ->values()
            ->all();
    }



    //==================================================================================================================
    // Par créneau : jour de la semaine × heure de début (carte de chaleur)
    //==================================================================================================================
    public static function get_par_creneau(array $filtres) : array {
        $duree = self::duree_sql;

        $prevues = self
            ::base_seances($filtres)
            ->leftJoinSub(self::sous_requete_effectifs(), 'eff', 'eff.groupe_id', '=', 'seances.groupe_id')
            ->groupByRaw('WEEKDAY(seances.date), seances.heure_debut')
            ->selectRaw("WEEKDAY(seances.date) as jour, TIME_FORMAT(seances.heure_debut, '%H:%i') as heure, SUM(($duree) * COALESCE(eff.effectif, 0)) as heures_prevues")
            ->get();

        $absences = self
            ::base_absences($filtres)
            ->groupByRaw('WEEKDAY(seances.date), seances.heure_debut')
            ->selectRaw("WEEKDAY(seances.date) as jour, TIME_FORMAT(seances.heure_debut, '%H:%i') as heure, SUM($duree) as heures_absence")
            ->get()
            ->keyBy(fn($ligne) => "{$ligne->jour}-{$ligne->heure}");

        $heures = $prevues->pluck('heure')
                          ->unique()
                          ->sort()
                          ->values()
                          ->all();

        $cellules = [];

        foreach ($heures as $heure) {
            $ligne = [];

            foreach (array_keys(self::jours_semaine) as $jour) {
                $prevu = $prevues->first(fn($valeur) => (int) $valeur->jour === $jour && $valeur->heure === $heure);

                $ligne[] = $prevu && $prevu->heures_prevues > 0
                    ? self::taux((float) ($absences["{$jour}-{$heure}"]->heures_absence ?? 0), (float) $prevu->heures_prevues)
                    : null;
            }

            $cellules[] = $ligne;
        }

        $valeurs = array_filter(array_merge(...($cellules ?: [[]])), fn($valeur) => $valeur !== null);

        return [
            'jours'    => self::jours_semaine,
            'heures'   => $heures,
            'cellules' => $cellules,
            'max'      => $valeurs ? max($valeurs) : 0,
        ];
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
            ::avec_couleur(self::base_absences($filtres))
            ->join('etudiants', 'etudiants.id', '=', 'absences.etudiant_id')
            ->groupBy('etudiants.id', 'etudiants.cle', 'etudiants.cne', 'etudiants.nom', 'etudiants.prenom')
            ->selectRaw("
                etudiants.cle, etudiants.cne, etudiants.nom, etudiants.prenom,
                MAX(groupes.nom) as groupe, MAX(seances.groupe_id) as groupe_id,
                MAX(" . self::couleur_sql . ") as couleur,
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
                'couleur'           => $ligne->couleur,
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



    //==================================================================================================================
    // Taux d'absence par semaine : comparable d'une semaine à l'autre, même si le nombre de séances varie
    //==================================================================================================================
    public static function get_evolution_taux_par_semaine(array $filtres) : array {
        $duree = self::duree_sql;

        $prevues = self
            ::base_seances($filtres)
            ->leftJoinSub(self::sous_requete_effectifs(), 'eff', 'eff.groupe_id', '=', 'seances.groupe_id')
            ->groupByRaw('YEARWEEK(seances.date, 3)')
            ->selectRaw("YEARWEEK(seances.date, 3) as semaine, MIN(seances.date) as premier_jour, SUM(($duree) * COALESCE(eff.effectif, 0)) as heures_prevues")
            ->orderBy('semaine')
            ->get();

        $absences = self
            ::base_absences($filtres)
            ->groupByRaw('YEARWEEK(seances.date, 3)')
            ->selectRaw("YEARWEEK(seances.date, 3) as semaine, COUNT(*) as nb_absences, SUM($duree) as heures_absence")
            ->get()
            ->keyBy('semaine');

        return [
            'labels'   => $prevues->map(fn($ligne) => Carbon::parse($ligne->premier_jour)
                                                            ->startOfWeek()
                                                            ->format('d/m'))
                                  ->all(),
            'taux'     => $prevues->map(fn($ligne) => self::taux((float) ($absences[$ligne->semaine]->heures_absence ?? 0), (float) $ligne->heures_prevues))
                                  ->all(),
            'absences' => $prevues->map(fn($ligne) => (int) ($absences[$ligne->semaine]->nb_absences ?? 0))
                                  ->all(),
        ];
    }
}
