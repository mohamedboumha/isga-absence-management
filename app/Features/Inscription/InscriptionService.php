<?php

namespace App\Features\Inscription;

use App\Features\Absence\Absence;
use App\Features\AnneeUniversitaire\AnneeUniversitaire;
use App\Features\Etudiant\Etudiant;
use App\Features\Groupe\Groupe;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class InscriptionService {
    //==================================================================================================================
    // Décisions de fin d'année (posées au passage d'année, étape 4)
    //==================================================================================================================
    const string decision_admis      = 'ADMIS';
    const string decision_redoublant = 'REDOUBLANT';
    const string decision_diplome    = 'DIPLOME';
    const string decision_sortant    = 'SORTANT';

    const array decisions = [
        self::decision_admis      => "Admis",
        self::decision_redoublant => "Redoublant",
        self::decision_diplome    => "Diplômé",
        self::decision_sortant    => "Sortant",
    ];


    //==================================================================================================================
    // Inscrit l'étudiant dans un groupe pour l'année de ce groupe (ou change de groupe dans la même année)
    //==================================================================================================================
    public static function inscrire(Etudiant $etudiant, Groupe $groupe) : Inscription {
        $inscription = Inscription
            ::withTrashed()
            ->firstOrNew([
                             'etudiant_id'            => $etudiant->id,
                             'annee_universitaire_id' => $groupe->annee_universitaire_id,
                         ]);

        //==============================================================================================================
        // Changement de groupe en cours d'année : refusé si des absences existent dans l'ancien groupe
        //==============================================================================================================
        if ($inscription->exists && !$inscription->trashed() && $inscription->groupe_id !== $groupe->id
            && self::a_des_absences($inscription)) {
            throw ValidationException::withMessages([
                                                        'groupe_id' => "Cet étudiant a déjà des absences dans son groupe actuel : il ne peut plus changer de groupe cette année.",
                                                    ]);
        }

        if ($inscription->trashed()) {
            $inscription->restore();
        }

        $inscription->groupe_id = $groupe->id;
        $inscription->save();

        return $inscription;
    }



    //==================================================================================================================
    // Retire l'inscription d'une année (refusé si des absences y sont rattachées)
    //==================================================================================================================
    public static function desinscrire(Etudiant $etudiant, AnneeUniversitaire $annee) : void {
        $inscription = Inscription::query()
                                  ->where('etudiant_id', $etudiant->id)
                                  ->where('annee_universitaire_id', $annee->id)
                                  ->first();

        if (!$inscription) {
            return;
        }

        if (self::a_des_absences($inscription)) {
            throw ValidationException::withMessages([
                                                        'groupe_id' => "Cet étudiant a des absences en {$annee->libelle} : son inscription ne peut pas être retirée.",
                                                    ]);
        }

        $inscription->delete();
    }



    //==================================================================================================================
    // Absences de l'étudiant dans les séances du groupe de cette inscription
    //==================================================================================================================
    public static function a_des_absences(Inscription $inscription) : bool {
        return Absence
            ::query()
            ->where('etudiant_id', $inscription->etudiant_id)
            ->whereHas('seance', fn(Builder $query) => $query->where('groupe_id', $inscription->groupe_id))
            ->exists();
    }



    //==================================================================================================================
    // Historique d'un étudiant, année la plus récente en premier (BF-37)
    //==================================================================================================================
    public static function get_historique(Etudiant $etudiant) : array {
        return Inscription
            ::query()
            ->where('etudiant_id', $etudiant->id)
            ->with(['annee_universitaire', 'groupe.niveau_etude'])
            ->get()
            ->sortByDesc(fn(Inscription $inscription) => $inscription->annee_universitaire->date_debut?->format('Y-m-d'))
            ->map(fn(Inscription $inscription) => [
                'annee'    => $inscription->annee_universitaire->libelle,
                'active'   => $inscription->annee_universitaire->active,
                'groupe'   => $inscription->groupe->nom,
                'niveau'   => "{$inscription->groupe->niveau_etude->code} — {$inscription->groupe->niveau_etude->libelle}",
                'decision' => $inscription->decision,
            ])
            ->values()
            ->all();
    }
}
