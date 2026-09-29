<?php

namespace App\Features\Etudiant;

use App\_Core\Builders\Table\TableBuilder;
use App\_Core\Builders\Table\TableColumn;
use App\_Core\Services\RendersService;
use App\Features\Groupe\GroupeService;
use Illuminate\Validation\ValidationException;

class EtudiantService {
    //==================================================================================================================
    // Tableau de la liste (mode_list)
    //==================================================================================================================
    public static function get_table() : array {
        return TableBuilder
            ::new(Etudiant::query()
                          ->with(['groupe.annee_universitaire']))
            ->add_column(
                TableColumn
                    ::new()
                    ->label("CNE")
                    ->nom_colonne('cne')
                    ->render(RendersService::render_chaine)
                    ->triable()
                    ->cherchable()
            )
            ->add_column(
                TableColumn
                    ::new()
                    ->label("Nom")
                    ->nom_colonne('nom')
                    ->render(RendersService::render_chaine)
                    ->triable()
                    ->cherchable()
            )
            ->add_column(
                TableColumn
                    ::new()
                    ->label("Prénom")
                    ->nom_colonne('prenom')
                    ->render(RendersService::render_chaine)
                    ->triable()
                    ->cherchable()
            )
            ->add_column(
                TableColumn
                    ::new()
                    ->label("Groupe")
                    ->nom_colonne('groupe.nom')
                    ->render(RendersService::render_chaine)
            )
            ->add_column(
                TableColumn
                    ::new()
                    ->label("Année")
                    ->nom_colonne('groupe.annee_universitaire.libelle')
                    ->render(RendersService::render_chaine)
            )
            ->default_tri('nom')
            ->row_url(fn(Etudiant $etudiant) => route('etudiant.detail', ['cle' => $etudiant->cle]))
            ->get();
    }



    public static function get_selects() : array {
        return [
            'groupes' => GroupeService::get_groupes_pour_select(),
        ];
    }



    public static function process_update_or_create(?string $cle, array $attributes) : Etudiant {
        return Etudiant::update_by_cle_or_create($cle, $attributes);
    }



    public static function process_delete(string $cle) : void {
        $etudiant = self::get_or_fail($cle);

        if (!$etudiant->can_be_deleted()) {
            throw ValidationException::withMessages([
                                                        'etudiant' => "L'étudiant {$etudiant->nom_complet} ne peut pas être supprimé (il a des absences).",
                                                    ]);
        }

        $etudiant->delete();
    }



    public static function get_or_fail(string $cle) : Etudiant {
        $etudiant = Etudiant::get_by_cle($cle);

        abort_if(!$etudiant, 404, "Étudiant introuvable");

        return $etudiant;
    }
}
