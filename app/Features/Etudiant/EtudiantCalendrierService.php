<?php

namespace App\Features\Etudiant;

use App\Features\Absence\Absence;
use App\Features\AnneeUniversitaire\AnneeUniversitaireService;
use App\Features\Groupe\GroupeRegistreService;
use App\Features\Seance\Seance;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class EtudiantCalendrierService {
    //==================================================================================================================
    // Un jour prend le statut le plus important de ses séances
    //==================================================================================================================
    const array priorites = [
        GroupeRegistreService::case_absent,
        GroupeRegistreService::case_justifie,
        GroupeRegistreService::case_a_faire,
        GroupeRegistreService::case_present,
        GroupeRegistreService::case_a_venir,
    ];

    const string jour_vide = 'vide';
    const string jour_hors = 'hors';

    const array jours_semaine = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];


    //==================================================================================================================
    // L'année active de l'étudiant : une colonne par semaine, une case par jour (lundi → samedi)
    // null s'il n'y a pas d'année active ou si l'étudiant n'est pas inscrit
    //==================================================================================================================
    public static function get(Etudiant $etudiant) : ?array {
        $annee       = AnneeUniversitaireService::get_active();
        $inscription = $etudiant->inscription_active;

        if (!$annee || !$inscription || !$annee->date_debut || !$annee->date_fin) {
            return null;
        }


        //==============================================================================================================
        // Séances du groupe de l'étudiant sur l'année (sauf annulées), et ses absences
        //==============================================================================================================
        $seances = Seance
            ::query()
            ->where('groupe_id', $inscription->groupe_id)
            ->where('annulee', false)
            ->whereBetween('date', [$annee->date_debut->format('Y-m-d'), $annee->date_fin->format('Y-m-d')])
            ->with('module')
            ->orderBy('date')
            ->orderBy('heure_debut')
            ->get();

        $absences = Absence
            ::query()
            ->where('etudiant_id', $etudiant->id)
            ->whereIn('seance_id', $seances->modelKeys())
            ->get()
            ->keyBy('seance_id');

        $par_jour = $seances->groupBy(fn(Seance $seance) => $seance->date->format('Y-m-d'));


        //==============================================================================================================
        // Semaines, du lundi de la première semaine de l'année à la fin de l'année
        // Les dates de l'application sont immuables : addWeek() renvoie une NOUVELLE date, qu'il faut réaffecter
        //==============================================================================================================
        $semaines = [];
        $lundi    = $annee->date_debut->copy()
                                      ->startOfWeek(CarbonInterface::MONDAY);

        while ($lundi->lte($annee->date_fin)) {
            $jours = [];

            for ($decalage = 0; $decalage < count(self::jours_semaine); $decalage++) {
                $jour = $lundi->copy()
                              ->addDays($decalage);

                $jours[] = $jour->lt($annee->date_debut) || $jour->gt($annee->date_fin)
                    ? ['statut' => self::jour_hors, 'libelle' => null]
                    : self::get_jour($jour, $par_jour->get($jour->format('Y-m-d'), collect()), $absences);
            }

            //==========================================================================================================
            // Nom du mois au-dessus de la semaine qui contient le 1er du mois (ou la première semaine)
            //==========================================================================================================
            $nouveau_mois = $semaines === [] || $lundi->day <= 7;

            $semaines[] = [
                'mois'  => $nouveau_mois ? $lundi->copy()
                                                 ->addDays(6)
                                                 ->locale('fr')
                                                 ->isoFormat('MMM') : null,
                'jours' => $jours,
            ];

            $lundi = $lundi->copy()
                           ->addWeek();
        }

        return [
            'annee'         => $annee->libelle,
            'jours_semaine' => self::jours_semaine,
            'semaines'      => $semaines,
        ];
    }



    //==================================================================================================================
    // Un jour : son statut (le plus important de ses séances) et son libellé au survol
    //==================================================================================================================
    protected static function get_jour(CarbonInterface $jour, Collection $seances, Collection $absences) : array {
        $date_libelle = ucfirst($jour->copy()
                                     ->locale('fr')
                                     ->isoFormat('dddd D MMMM'));

        if ($seances->isEmpty()) {
            return ['statut' => self::jour_vide, 'libelle' => "{$date_libelle} : pas de séance"];
        }

        $statuts = $seances->map(fn(Seance $seance) => GroupeRegistreService::get_case($seance, $absences->get($seance->id)));

        $statut = collect(self::priorites)->first(fn(string $priorite) => $statuts->contains($priorite)) ?? self::jour_vide;


        //==============================================================================================================
        // "Lundi 29 septembre : 2 séances, absent en ALG-01"
        //==============================================================================================================
        $libelle = "{$date_libelle} : {$seances->count()} séance" . ($seances->count() > 1 ? 's' : '');

        $absent_en   = $seances->filter(fn(Seance $seance, int $index) => $statuts[$index] === GroupeRegistreService::case_absent)
                               ->map(fn(Seance $seance) => $seance->module->code);
        $justifie_en = $seances->filter(fn(Seance $seance, int $index) => $statuts[$index] === GroupeRegistreService::case_justifie)
                               ->map(fn(Seance $seance) => $seance->module->code);

        if ($absent_en->isNotEmpty()) {
            $libelle .= ", absent en {$absent_en->join(', ')}";
        }

        if ($justifie_en->isNotEmpty()) {
            $libelle .= ", absence justifiée en {$justifie_en->join(', ')}";
        }

        if ($statut === GroupeRegistreService::case_a_faire) {
            $libelle .= ", appel à faire";
        }

        return ['statut' => $statut, 'libelle' => $libelle];
    }
}
