<?php

namespace App\Features\NiveauEtude;

use App\_Core\Builders\Table\TableBuilder;
use App\_Core\Builders\Table\TableColumn;
use App\_Core\Services\RendersService;
use App\Features\Cycle\CycleService;
use App\Features\Filiere\FiliereService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class NiveauEtudeService {
    public static function get_table() : array {
        return TableBuilder
            ::new(NiveauEtude::query()
                             ->with(['cycle', 'filiere', 'suivants']))
            ->add_column(TableColumn::new()
                                    ->label("Code")
                                    ->nom_colonne('code')
                                    ->render(RendersService::render_chaine)
                                    ->triable()
                                    ->cherchable())
            ->add_column(TableColumn::new()
                                    ->label("Libellé")
                                    ->nom_colonne('libelle')
                                    ->render(RendersService::render_chaine)
                                    ->triable()
                                    ->cherchable())
            ->add_column(TableColumn::new()
                                    ->label("Cycle")
                                    ->nom_colonne('cycle.nom')
                                    ->render(RendersService::render_chaine))
            ->add_column(TableColumn::new()
                                    ->label("Année")
                                    ->nom_colonne('annee_cycle')
                                    ->render(RendersService::render_nombre)
                                    ->triable())
            ->add_column(TableColumn::new()
                                    ->label("Filière")
                                    ->nom_colonne('filiere.code')
                                    ->render(RendersService::render_chaine))
            ->add_column(TableColumn::new()
                                    ->label("Semestres")
                                    ->nom_colonne('nb_semestres')
                                    ->render(RendersService::render_nombre))
            ->add_column(TableColumn::new()
                                    ->label("Année suivante")
                                    ->nom_colonne('suivants_render')
                                    ->render(RendersService::render_chaine))
            ->default_tri('code')
            ->row_url(fn(NiveauEtude $niveau) => route('niveau-etude.detail', ['cle' => $niveau->cle]))
            ->get();
    }



    //==================================================================================================================
    // Selects du formulaire ; chaque niveau porte son cycle et son année (pour filtrer les parcours côté Vue)
    //==================================================================================================================
    public static function get_selects() : array {
        return [
            'cycles'   => CycleService::get_cycles_pour_select(),
            'filieres' => FiliereService::get_filieres_pour_select(),
            'niveaux'  => NiveauEtude
                ::query()
                ->orderBy('code')
                ->get()
                ->map(fn(NiveauEtude $niveau) => [
                    'valeur'      => $niveau->id,
                    'label'       => "{$niveau->code} — {$niveau->libelle}",
                    'cycle_id'    => $niveau->cycle_id,
                    'annee_cycle' => $niveau->annee_cycle,
                ])
                ->all(),
        ];
    }



    public static function get_niveaux_pour_select() : array {
        return NiveauEtude
            ::query()
            ->orderBy('code')
            ->get()
            ->map(fn(NiveauEtude $niveau) => ['valeur' => $niveau->id, 'label' => "{$niveau->code} — {$niveau->libelle}"])
            ->all();
    }



    //==================================================================================================================
    // Enregistrement du niveau et de son parcours, ensemble
    //==================================================================================================================
    public static function process_update_or_create(?string $cle, array $attributes) : NiveauEtude {
        $suivants = $attributes['suivants'] ?? [];
        unset($attributes['suivants']);

        return DB::transaction(function () use ($cle, $attributes, $suivants) {
            $niveau = NiveauEtude::update_by_cle_or_create($cle, $attributes);

            //==========================================================================================================
            // Dernière année du cycle : pas de niveau suivant (diplôme)
            //==========================================================================================================
            $niveau->suivants()
                   ->sync($niveau->est_derniere_annee ? [] : $suivants);

            return $niveau;
        });
    }



    public static function process_delete(string $cle) : void {
        $niveau = self::get_or_fail($cle);

        if (!$niveau->can_be_deleted()) {
            throw ValidationException::withMessages([
                                                        'niveau' => "Le niveau {$niveau->code} ne peut pas être supprimé : il contient des groupes ou des modules.",
                                                    ]);
        }

        $niveau->delete();
    }



    public static function get_or_fail(string $cle) : NiveauEtude {
        $niveau = NiveauEtude::get_by_cle($cle);

        abort_if(!$niveau, 404, "Niveau d'études introuvable");

        return $niveau;
    }

    //==================================================================================================================
    // Pour le select des groupes et des modules ; le nombre de semestres accompagne chaque niveau
    //==================================================================================================================
    public static function get_niveaux_avec_semestres_pour_select() : array {
        return NiveauEtude
            ::query()
            ->orderBy('code')
            ->get()
            ->map(fn(NiveauEtude $niveau) => [
                'valeur'       => $niveau->id,
                'label'        => "{$niveau->code} — {$niveau->libelle}",
                'nb_semestres' => $niveau->nb_semestres,
            ])
            ->all();
    }
}
