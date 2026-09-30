<?php

namespace App\Features\Export;

use App\Features\Absence\Absence;
use App\Features\Etudiant\Etudiant;
use App\Features\Groupe\Groupe;
use App\Features\Seance\Seance;
use App\Features\Statistique\StatistiqueService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ExportService {
    //==================================================================================================================
    // Période : celle demandée (?date_debut=&date_fin=), sinon l'année active
    //==================================================================================================================
    public static function get_periode(?string $date_debut, ?string $date_fin) : array {
        $defaut = StatistiqueService::get_filtres_par_defaut();

        return [
            'date_debut' => $date_debut ?: $defaut['date_debut'],
            'date_fin'   => $date_fin ?: $defaut['date_fin'],
        ];
    }



    public static function get_periode_render(array $periode) : string {
        return 'Du ' . Carbon::parse($periode['date_debut'])->format('d/m/Y') . ' au ' . Carbon::parse($periode['date_fin'])->format('d/m/Y');
    }



    //==================================================================================================================
    // Séances tenues d'un groupe (non annulées, appel fait) dans la période
    //==================================================================================================================
    protected static function get_seances_tenues(int $groupe_id, array $periode) : \Illuminate\Database\Eloquent\Collection {
        return Seance
            ::query()
            ->where('groupe_id', $groupe_id)
            ->non_annulees()
            ->whereNotNull('appel_fait_le')
            ->whereBetween('date', [$periode['date_debut'], $periode['date_fin']])
            ->get();
    }



    //==================================================================================================================
    // Relevé d'absences d'un étudiant (BF-27)
    //==================================================================================================================
    public static function get_releve_etudiant(Etudiant $etudiant, array $periode) : array {
        $etudiant->loadMissing(['groupe.filiere', 'groupe.annee_universitaire']);

        $absences = Absence
            ::query()
            ->where('etudiant_id', $etudiant->id)
            ->whereHas('seance', fn(Builder $query) => $query
                ->where('annulee', false)
                ->whereBetween('date', [$periode['date_debut'], $periode['date_fin']]))
            ->with('seance.module')
            ->get()
            ->sortBy(fn(Absence $absence) => $absence->seance->date?->format('Y-m-d') . ' ' . $absence->seance->heure_debut)
            ->values();

        $heures_prevues = self::get_seances_tenues($etudiant->groupe_id, $periode)->sum(fn(Seance $seance) => $seance->duree_heures);
        $heures_absence = $absences->sum(fn(Absence $absence) => $absence->seance->duree_heures);

        return [
            'etudiant' => $etudiant,
            'periode'  => self::get_periode_render($periode),
            'absences' => $absences->map(fn(Absence $absence) => [
                'date'      => $absence->seance->date?->format('d/m/Y'),
                'horaire'   => $absence->seance->horaire_render,
                'module'    => "{$absence->seance->module->code} — {$absence->seance->module->intitule}",
                'type'      => $absence->seance->type,
                'justifiee' => $absence->justifiee,
                'remarque'  => $absence->remarque,
            ])->all(),
            'total'    => [
                'nb_absences'   => $absences->count(),
                'nb_justifiees' => $absences->where('justifiee', true)->count(),
                'heures'        => round($heures_absence, 1),
                'taux'          => StatistiqueService::taux($heures_absence, $heures_prevues),
            ],
        ];
    }



    //==================================================================================================================
    // Rapport d'un groupe : chaque étudiant avec son taux (BF-27)
    //==================================================================================================================
    public static function get_rapport_groupe(Groupe $groupe, array $periode) : array {
        $groupe->loadMissing(['filiere', 'annee_universitaire']);

        $seances        = self::get_seances_tenues($groupe->id, $periode);
        $heures_prevues = $seances->sum(fn(Seance $seance) => $seance->duree_heures);

        $absences_par_etudiant = Absence
            ::query()
            ->whereIn('seance_id', $seances->modelKeys())
            ->with('seance')
            ->get()
            ->groupBy('etudiant_id');

        $lignes = $groupe
            ->etudiants()
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get()
            ->map(function (Etudiant $etudiant) use ($absences_par_etudiant, $heures_prevues) {
                $absences = $absences_par_etudiant->get($etudiant->id, collect());
                $heures   = $absences->sum(fn(Absence $absence) => $absence->seance->duree_heures);

                return [
                    'cne'               => $etudiant->cne,
                    'nom_complet'       => $etudiant->nom_complet,
                    'nb_absences'       => $absences->count(),
                    'nb_non_justifiees' => $absences->where('justifiee', false)->count(),
                    'heures'            => round($heures, 1),
                    'taux'              => StatistiqueService::taux($heures, $heures_prevues),
                ];
            });

        return [
            'groupe'     => $groupe,
            'periode'    => self::get_periode_render($periode),
            'nb_seances' => $seances->count(),
            'heures'     => round($heures_prevues, 1),
            'lignes'     => $lignes->all(),
            'total'      => [
                'nb_absences'       => $lignes->sum('nb_absences'),
                'nb_non_justifiees' => $lignes->sum('nb_non_justifiees'),
                'heures'            => round($lignes->sum('heures'), 1),
                'taux'              => StatistiqueService::taux($lignes->sum('heures'), $heures_prevues * max($lignes->count(), 1)),
            ],
        ];
    }



    //==================================================================================================================
    // Feuille de présence vierge d'une séance (BF-27)
    //==================================================================================================================
    public static function get_feuille_presence(Seance $seance) : array {
        $seance->loadMissing(['module', 'groupe', 'enseignant']);

        return [
            'seance'    => $seance,
            'etudiants' => $seance->groupe->etudiants()->orderBy('nom')->orderBy('prenom')->get(),
        ];
    }
}
