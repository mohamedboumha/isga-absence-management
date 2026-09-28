<?php

namespace App\Features\Semestre;

use App\_Core\Builders\Table\TableBuilder;
use App\_Core\Builders\Table\TableColumn;
use App\_Core\Services\RendersService;
use App\Features\AnneeUniversitaire\AnneeUniversitaire;
use Illuminate\Validation\ValidationException;

class SemestreService {
    //==================================================================================================================
    // Tableau de la liste (mode_list)
    //==================================================================================================================
    public static function get_table() : array {
        return TableBuilder
            ::new(Semestre::query()
                          ->with('annee_universitaire'))
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
                    ->label("Année universitaire")
                    ->nom_colonne('annee_universitaire.libelle')
                    ->render(RendersService::render_chaine)
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
            ->default_tri('date_debut', 'desc')
            ->row_url(fn(Semestre $semestre) => route('semestre.detail', ['cle' => $semestre->cle]))
            ->get();
    }



    //==================================================================================================================
    // Liste des années pour le select du formulaire
    //==================================================================================================================
    public static function get_annees_pour_select() : array {
        return AnneeUniversitaire::list_pour_select('id', 'libelle', 'date_debut');
    }



    public static function process_update_or_create(?string $cle, array $attributes) : Semestre {
        return Semestre::update_by_cle_or_create($cle, $attributes);
    }



    public static function process_delete(string $cle) : void {
        $semestre = self::get_or_fail($cle);

        if (!$semestre->can_be_deleted()) {
            throw ValidationException::withMessages([
                                                        'semestre' => "Le semestre {$semestre->libelle} ne peut pas être supprimé.",
                                                    ]);
        }

        $semestre->delete();
    }



    public static function get_or_fail(string $cle) : Semestre {
        $semestre = Semestre::get_by_cle($cle);

        abort_if(!$semestre, 404, "Semestre introuvable");

        return $semestre;
    }
}
