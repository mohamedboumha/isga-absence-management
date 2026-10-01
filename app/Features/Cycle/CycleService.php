<?php

namespace App\Features\Cycle;

use App\_Core\Builders\Table\TableBuilder;
use App\_Core\Builders\Table\TableColumn;
use App\_Core\Services\RendersService;
use Illuminate\Validation\ValidationException;

class CycleService {
    public static function get_table() : array {
        return TableBuilder
            ::new(Cycle::query()
                       ->withCount(['filieres', 'niveaux']))
            ->add_column(TableColumn::new()
                                    ->label("Code")
                                    ->nom_colonne('code')
                                    ->render(RendersService::render_chaine)
                                    ->triable()
                                    ->cherchable())
            ->add_column(TableColumn::new()
                                    ->label("Nom")
                                    ->nom_colonne('nom')
                                    ->render(RendersService::render_chaine)
                                    ->triable()
                                    ->cherchable())
            ->add_column(TableColumn::new()
                                    ->label("Durée (années)")
                                    ->nom_colonne('nb_annees')
                                    ->render(RendersService::render_nombre)
                                    ->triable())
            ->add_column(TableColumn::new()
                                    ->label("Filières")
                                    ->nom_colonne('filieres_count')
                                    ->render(RendersService::render_nombre))
            ->add_column(TableColumn::new()
                                    ->label("Niveaux")
                                    ->nom_colonne('niveaux_count')
                                    ->render(RendersService::render_nombre))
            ->default_tri('nom')
            ->row_url(fn(Cycle $cycle) => route('cycle.detail', ['cle' => $cycle->cle]))
            ->get();
    }



    //==================================================================================================================
    // Pour les selects : la durée accompagne chaque cycle (utile pour limiter l'année d'un niveau)
    //==================================================================================================================
    public static function get_cycles_pour_select() : array {
        return Cycle
            ::query()
            ->orderBy('nom')
            ->get()
            ->map(fn(Cycle $cycle) => [
                'valeur'    => $cycle->id,
                'label'     => "{$cycle->nom} ({$cycle->nb_annees} an" . ($cycle->nb_annees > 1 ? 's' : '') . ")",
                'nb_annees' => $cycle->nb_annees,
            ])
            ->all();
    }



    public static function process_update_or_create(?string $cle, array $attributes) : Cycle {
        return Cycle::update_by_cle_or_create($cle, $attributes);
    }



    public static function process_delete(string $cle) : void {
        $cycle = self::get_or_fail($cle);

        if (!$cycle->can_be_deleted()) {
            throw ValidationException::withMessages([
                                                        'cycle' => "Le cycle {$cycle->nom} ne peut pas être supprimé : il contient des filières ou des niveaux.",
                                                    ]);
        }

        $cycle->delete();
    }



    public static function get_or_fail(string $cle) : Cycle {
        $cycle = Cycle::get_by_cle($cle);

        abort_if(!$cycle, 404, "Cycle introuvable");

        return $cycle;
    }
}
