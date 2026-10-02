<?php

namespace App\Features\Groupe;

use App\Features\Absence\Absence;
use App\Features\Etudiant\Etudiant;
use App\Features\Module\Module;
use App\Features\Seance\Seance;
use Illuminate\Support\Carbon;

class GroupeRegistreService {
    //==================================================================================================================
    // Statuts d'une case du registre (mêmes clés côté Vue)
    //==================================================================================================================
    const string case_present  = 'present';
    const string case_absent   = 'absent';
    const string case_justifie = 'justifie';
    const string case_a_faire  = 'a_faire';
    const string case_a_venir  = 'a_venir';
    const string case_annulee  = 'annulee';



    //==================================================================================================================
    // Registre d'un mois : une ligne par étudiant, une colonne par séance
    //==================================================================================================================
    public static function get(Groupe $groupe, ?string $mois, mixed $module_id) : array {
        $groupe->loadMissing('annee_universitaire');


        //==============================================================================================================
        // Mois de l'année du groupe ; par défaut le mois courant (ou le plus proche dans l'année)
        //==============================================================================================================
        $mois_options = self::get_mois_options($groupe);
        $valeurs      = array_column($mois_options, 'valeur');
        $mois         = in_array($mois, $valeurs, true) ? $mois : self::get_mois_par_defaut($valeurs);
        $index        = (int) array_search($mois, $valeurs, true);

        $debut_mois = Carbon::createFromFormat('Y-m-d', "{$mois}-01")->startOfDay();
        $fin_mois   = $debut_mois->copy()->endOfMonth();


        //==============================================================================================================
        // Modules du niveau du groupe (filtre facultatif)
        //==============================================================================================================
        $modules   = Module::query()->where('niveau_etude_id', $groupe->niveau_etude_id)->orderBy('code')->get();
        $module_id = $modules->contains('id', (int) $module_id) ? (int) $module_id : null;


        //==============================================================================================================
        // Séances du mois, étudiants du groupe, absences
        //==============================================================================================================
        $seances = Seance
            ::query()
            ->where('groupe_id', $groupe->id)
            ->whereBetween('date', [$debut_mois->format('Y-m-d'), $fin_mois->format('Y-m-d')])
            ->when($module_id, fn($query) => $query->where('module_id', $module_id))
            ->with(['module.niveau_etude.cycle', 'module.niveau_etude.filiere.cycle'])
            ->orderBy('date')
            ->orderBy('heure_debut')
            ->get();

        $etudiants = $groupe->etudiants()->orderBy('nom')->orderBy('prenom')->get();

        $absences = Absence
            ::query()
            ->whereIn('seance_id', $seances->modelKeys())
            ->get()
            ->keyBy(fn(Absence $absence) => "{$absence->seance_id}-{$absence->etudiant_id}");

        return [
            'mois'           => $mois,
            'mois_label'     => $mois_options[$index]['label'],
            'mois_precedent' => $valeurs[$index - 1] ?? null,
            'mois_suivant'   => $valeurs[$index + 1] ?? null,
            'module'         => $module_id,
            'modules'        => $modules->map(fn(Module $module) => [
                'valeur' => $module->id,
                'label'  => "{$module->code} — {$module->intitule}",
            ])->all(),
            'colonnes'       => $seances->map(fn(Seance $seance) => [
                'cle'      => $seance->cle,
                'jour'     => ucfirst($seance->date->copy()->locale('fr')->isoFormat('ddd')),
                'date'     => $seance->date->format('d/m'),
                'horaire'  => $seance->horaire_render,
                'module'   => $seance->module->code,
                'intitule' => $seance->module->intitule,
                'couleur'  => $seance->module->niveau_etude->couleur_effective,
                'annulee'  => $seance->annulee,
                'url'      => route('seance.detail', ['cle' => $seance->cle]),
            ])->all(),
            'lignes'         => $etudiants->map(function (Etudiant $etudiant) use ($seances, $absences) {
                $cases = $seances
                    ->map(fn(Seance $seance) => self::get_case($seance, $absences->get("{$seance->id}-{$etudiant->id}")))
                    ->all();

                return [
                    'nom_complet' => $etudiant->nom_complet,
                    'cne'         => $etudiant->cne,
                    'url'         => route('etudiant.detail', ['cle' => $etudiant->cle]),
                    'cases'       => $cases,
                    'nb_absences' => count(array_filter($cases, fn(string $case) => in_array($case, [self::case_absent, self::case_justifie], true))),
                ];
            })->all(),
        ];
    }



    //==================================================================================================================
    // Statut d'une case : même logique que les pastilles (Seance::statut_cle) et les absences
    //==================================================================================================================
    public static function get_case(Seance $seance, ?Absence $absence) : string {
        if ($seance->annulee) {
            return self::case_annulee;
        }

        if ($seance->appel_fait_le) {
            if (!$absence) {
                return self::case_present;
            }

            return $absence->justifiee ? self::case_justifie : self::case_absent;
        }

        return $seance->date && $seance->date->lte(today()) ? self::case_a_faire : self::case_a_venir;
    }



    //==================================================================================================================
    // "2026-09" => "Septembre 2026", pour chaque mois de l'année du groupe
    // Les dates de l'application sont immuables : addMonth() renvoie une NOUVELLE date, qu'il faut réaffecter
    //==================================================================================================================
    protected static function get_mois_options(Groupe $groupe) : array {
        $annee = $groupe->annee_universitaire;
        $mois  = $annee->date_debut->copy()->startOfMonth();
        $fin   = $annee->date_fin->copy()->startOfMonth();

        $options = [];

        while ($mois->lte($fin)) {
            $options[] = [
                'valeur' => $mois->format('Y-m'),
                'label'  => ucfirst($mois->copy()->locale('fr')->isoFormat('MMMM YYYY')),
            ];

            $mois = $mois->copy()->addMonth();
        }

        return $options;
    }



    protected static function get_mois_par_defaut(array $valeurs) : string {
        $courant = today()->format('Y-m');

        if (in_array($courant, $valeurs, true)) {
            return $courant;
        }

        return $courant < $valeurs[0] ? $valeurs[0] : $valeurs[count($valeurs) - 1];
    }
}
