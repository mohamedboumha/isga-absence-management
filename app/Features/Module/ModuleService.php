<?php

namespace App\Features\Module;

use App\_Core\Builders\Table\TableBuilder;
use App\_Core\Builders\Table\TableColumn;
use App\_Core\Builders\Table\TableFilter;
use App\_Core\Services\RendersService;
use App\Features\Cycle\CycleService;
use App\Features\NiveauEtude\NiveauEtudeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class ModuleService {
    //==================================================================================================================
    // Tableau de la liste (mode_list) : le code en badge (couleur du niveau), l'intitulé dessous
    //==================================================================================================================
    public static function get_table() : array {
        return TableBuilder
            ::new(
                Module
                    ::query()
                    ->with(['niveau_etude.cycle', 'niveau_etude.filiere.cycle'])
                    ->withCount('enseignants')
            )
            ->add_column(
                TableColumn
                    ::new()
                    ->label("Module")
                    ->nom_colonne('code')
                    ->render(RendersService::render_badge)
                    ->couleur('niveau_etude.couleur_effective')
                    ->sous_texte('intitule')
                    ->triable()
                    ->recherche_sur(['code', 'intitule'])
            )
            ->add_column(
                TableColumn
                    ::new()
                    ->label("Niveau d'études")
                    ->nom_colonne('niveau_etude.code')
                    ->render(RendersService::render_chaine)
                    ->sous_texte('niveau_etude.cycle.nom')
            )
            ->add_column(
                TableColumn
                    ::new()
                    ->label("Semestre")
                    ->nom_colonne('semestre_render')
                    ->render(RendersService::render_chaine)
                    ->tri_sur('semestre')
            )
            ->add_column(
                TableColumn
                    ::new()
                    ->label("Volume")
                    ->nom_colonne('volume')
                    ->render(RendersService::render_chaine)
                    ->valeur(fn(Module $module) => "{$module->volume_horaire} h")
                    ->tri_sur('volume_horaire')
            )
            ->add_column(
                TableColumn
                    ::new()
                    ->label("Enseignants")
                    ->nom_colonne('enseignants_count')
                    ->render(RendersService::render_nombre)
            )
            //==========================================================================================================
            // Filtres
            //==========================================================================================================
            ->add_filtre(
                TableFilter
                    ::new('cycle')
                    ->label("Cycle")
                    ->select(CycleService::get_cycles_pour_select())
                    ->appliquer(fn(Builder $query, string $valeur) => $query->whereHas(
                        'niveau_etude',
                        fn(Builder $query) => $query->where('cycle_id', $valeur)
                    ))
            )
            ->add_filtre(
                TableFilter
                    ::new('niveau')
                    ->label("Niveau d'études")
                    ->select(NiveauEtudeService::get_niveaux_pour_select())
                    ->sur_colonne('niveau_etude_id')
            )
            ->add_filtre(
                TableFilter
                    ::new('semestre')
                    ->label("Semestre")
                    ->select(array_map(fn(int $numero) => ['valeur' => (string) $numero, 'label' => "Semestre {$numero}"], range(1, 4)))
                    ->sur_colonne('semestre')
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
            'niveaux' => NiveauEtudeService::get_niveaux_avec_semestres_pour_select(),
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
