<?php

namespace App\Features\AnneeUniversitaire;

use App\_Core\Services\RendersService;
use App\_Core\Builders\Table\TableColumn;
use App\_Core\Builders\Table\TableBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class AnneeUniversitaireService {
    //==================================================================================================================
    // Liste de toutes les années, la plus récente en premier
    //==================================================================================================================
    public static function get_liste() : Collection {
        return AnneeUniversitaire
            ::ordre_recent()
            ->get();
    }



    //==================================================================================================================
    // L'année en cours (null si aucune n'est active)
    //==================================================================================================================
    public static function get_active() : ?AnneeUniversitaire {
        return AnneeUniversitaire
            ::active()
            ->first();
    }



    //==================================================================================================================
    // Création (cle = null) ou modification (cle = celle de l'année)
    //==================================================================================================================
    public static function process_update_or_create(?string $cle, array $attributes) : AnneeUniversitaire {
        return AnneeUniversitaire::update_by_cle_or_create($cle, $attributes);
    }



    //==================================================================================================================
    // Rendre une année active (les autres sont désactivées par le model)
    //==================================================================================================================
    public static function activer(string $cle) : AnneeUniversitaire {
        $annee = self::get_or_fail($cle);

        $annee->active = true;
        $annee->save();

        return $annee;
    }



    //==================================================================================================================
    // Suppression logique (soft delete), refusée si le model l'interdit
    //==================================================================================================================
    public static function process_delete(string $cle) : void {
        $annee = self::get_or_fail($cle);

        if (!$annee->can_be_deleted()) {
            throw ValidationException::withMessages([
                                                        'annee' => "L'année {$annee->libelle} ne peut pas être supprimée (elle est active ou contient des semestres).",
                                                    ]);
        }

        $annee->delete();
    }



    //==================================================================================================================
    // Récupère l'année par sa clé ou renvoie une erreur 404
    //==================================================================================================================
    public static function get_or_fail(string $cle) : AnneeUniversitaire {
        $annee = AnneeUniversitaire::get_by_cle($cle);

        abort_if(!$annee, 404, "Année universitaire introuvable");

        return $annee;
    }


    //==================================================================================================================
    // Tableau de la liste (mode_list)
    //==================================================================================================================
    public static function get_table() : array {
        return TableBuilder
            ::new(AnneeUniversitaire::query())
            ->add_column(
                TableColumn
                    ::new()
                    ->label("Libellé")
                    ->nom_colonne('libelle')
                    ->render(RendersService::render_chaine)
                    ->triable()
                    ->cherchable()
            )
            ->add_column(
                TableColumn
                    ::new()
                    ->label("Date de début")
                    ->nom_colonne('date_debut')
                    ->render(RendersService::render_date)
                    ->triable()
            )
            ->add_column(
                TableColumn
                    ::new()
                    ->label("Date de fin")
                    ->nom_colonne('date_fin')
                    ->render(RendersService::render_date)
                    ->triable()
            )
            ->add_column(
                TableColumn
                    ::new()
                    ->label("Année en cours")
                    ->nom_colonne('active')
                    ->render(RendersService::render_boolean)
                    ->triable()
            )
            ->default_tri('date_debut', 'desc')
            ->row_url(fn(AnneeUniversitaire $annee) => route('annee-universitaire.detail', ['cle' => $annee->cle]))
            ->get();
    }
}
