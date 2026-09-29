<?php

namespace App\Features\Module;

use App\_Core\Builders\Table\TableBuilder;
use App\_Core\Builders\Table\TableColumn;
use App\_Core\Services\RendersService;
use App\Features\Filiere\FiliereService;
use Illuminate\Validation\ValidationException;

class ModuleService {
    //==================================================================================================================
    // Tableau de la liste (mode_list)
    //==================================================================================================================
    public static function get_table() : array {
        return TableBuilder
            ::new(Module::query()
                        ->with('filiere'))
            ->add_column(
                TableColumn
                    ::new()
                    ->label("Code")
                    ->nom_colonne('code')
                    ->render(RendersService::render_chaine)
                    ->triable()
                    ->cherchable()
            )
            ->add_column(
                TableColumn
                    ::new()
                    ->label("Intitulé")
                    ->nom_colonne('intitule')
                    ->render(RendersService::render_chaine)
                    ->triable()
                    ->cherchable()
            )
            ->add_column(
                TableColumn
                    ::new()
                    ->label("Filière")
                    ->nom_colonne('filiere.code')
                    ->render(RendersService::render_chaine)
            )
            ->add_column(
                TableColumn
                    ::new()
                    ->label("Semestre")
                    ->nom_colonne('semestre')
                    ->render(RendersService::render_chaine)
                    ->triable()
                    ->cherchable()
            )
            ->add_column(
                TableColumn
                    ::new()
                    ->label("Niveau")
                    ->nom_colonne('niveau')
                    ->render(RendersService::render_chaine)
            )
            ->add_column(
                TableColumn
                    ::new()
                    ->label("Volume (h)")
                    ->nom_colonne('volume_horaire')
                    ->render(RendersService::render_nombre)
                    ->triable()
            )
            ->default_tri('code')
            ->row_url(fn(Module $module) => route('module.detail', ['cle' => $module->cle]))
            ->get();
    }



    //==================================================================================================================
    // Listes pour les selects du formulaire
    //==================================================================================================================
    public static function get_selects() : array {
        return [
            'filieres'  => FiliereService::get_filieres_pour_select(),
            'semestres' => FiliereService::get_semestres_pour_select(),
        ];
    }



    public static function process_update_or_create(?string $cle, array $attributes) : Module {
        return Module::update_by_cle_or_create($cle, $attributes);
    }



    public static function process_delete(string $cle) : void {
        $module = self::get_or_fail($cle);

        if (!$module->can_be_deleted()) {
            throw ValidationException::withMessages([
                                                        'module' => "Le module {$module->code} ne peut pas être supprimé (il a des séances).",
                                                    ]);
        }

        $module->delete();
    }



    public static function get_or_fail(string $cle) : Module {
        $module = Module::get_by_cle($cle);

        abort_if(!$module, 404, "Module introuvable");

        return $module;
    }
}
