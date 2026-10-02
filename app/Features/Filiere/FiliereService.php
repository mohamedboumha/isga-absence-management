<?php

namespace App\Features\Filiere;

use App\_Core\Builders\Table\TableBuilder;
use App\_Core\Builders\Table\TableColumn;
use App\_Core\Services\RendersService;
use Illuminate\Validation\ValidationException;

class FiliereService {
    //==================================================================================================================
    // Tableau de la liste (mode_list) : la filière et son cycle en badges, chacun à sa couleur
    //==================================================================================================================
    public static function get_table() : array {
        return TableBuilder
            ::new(Filiere::query()
                         ->with('cycle')
                         ->withCount('niveaux'))
            ->add_column(
                TableColumn
                    ::new()
                    ->label("Code")
                    ->nom_colonne('code')
                    ->render(RendersService::render_badge)
                    ->couleur('couleur_effective')
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
                    ->label("Cycle")
                    ->nom_colonne('cycle.code')
                    ->render(RendersService::render_badge)
                    ->couleur('cycle.couleur')
                    ->sous_texte('cycle.nom')
            )
            ->add_column(
                TableColumn
                    ::new()
                    ->label("Niveaux")
                    ->nom_colonne('niveaux_count')
                    ->render(RendersService::render_nombre)
            )
            ->default_tri('code')
            ->row_url(fn(Filiere $filiere) => route('filiere.detail', ['cle' => $filiere->cle]))
            ->get();
    }



    //==================================================================================================================
    // Pour les selects : chaque filière porte son cycle (pour filtrer côté Vue)
    //==================================================================================================================
    public static function get_filieres_pour_select() : array {
        return Filiere
            ::query()
            ->orderBy('code')
            ->get()
            ->map(fn(Filiere $filiere) => [
                'valeur'   => $filiere->id,
                'label'    => "{$filiere->code} — {$filiere->nom}",
                'cycle_id' => $filiere->cycle_id,
            ])
            ->all();
    }



    public static function process_update_or_create(?string $cle, array $attributes) : Filiere {
        return Filiere::update_by_cle_or_create($cle, $attributes);
    }



    public static function process_delete(string $cle) : void {
        $filiere = self::get_or_fail($cle);

        if (!$filiere->can_be_deleted()) {
            throw ValidationException::withMessages([
                                                        'filiere' => "La filière {$filiere->code} ne peut pas être supprimée : des niveaux d'études l'utilisent.",
                                                    ]);
        }

        $filiere->delete();
    }



    public static function get_or_fail(string $cle) : Filiere {
        $filiere = Filiere::get_by_cle($cle);

        abort_if(!$filiere, 404, "Filière introuvable");

        return $filiere;
    }
}
