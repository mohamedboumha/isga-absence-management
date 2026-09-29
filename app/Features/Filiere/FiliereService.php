<?php

namespace App\Features\Filiere;

use App\_Core\Builders\Table\TableBuilder;
use App\_Core\Builders\Table\TableColumn;
use App\_Core\Services\RendersService;
use Illuminate\Validation\ValidationException;

class FiliereService {
    //==================================================================================================================
    // Niveaux d'études (utilisés par la feature Groupe)
    //==================================================================================================================
    const string niveau_l1 = 'L1';
    const string niveau_l2 = 'L2';
    const string niveau_l3 = 'L3';
    const string niveau_m1 = 'M1';
    const string niveau_m2 = 'M2';

    const array niveaux = [
        self::niveau_l1,
        self::niveau_l2,
        self::niveau_l3,
        self::niveau_m1,
        self::niveau_m2,
    ];

    const array semestres = ['S1', 'S2', 'S3', 'S4', 'S5', 'S6', 'S7', 'S8', 'S9', 'S10'];



    public static function get_niveaux_pour_select() : array {
        return array_map(fn(string $niveau) => ['valeur' => $niveau, 'label' => $niveau], self::niveaux);
    }



    public static function get_semestres_pour_select() : array {
        return array_map(
            fn(string $semestre) => ['valeur' => $semestre, 'label' => "$semestre (" . self::get_niveau_by_semestre($semestre) . ")"],
            self::semestres
        );
    }



    public static function get_niveau_by_semestre(?string $semestre) : ?string {
        //==============================================================================================================
        // "S5" => 5 => (5 - 1) / 2 = 2 => niveaux[2] = "L3"
        //==============================================================================================================
        if (!$semestre || !in_array($semestre, self::semestres, true)) {
            return null;
        }

        $numero = (int) substr($semestre, 1);

        return self::niveaux[intdiv($numero - 1, 2)];
    }



    //==================================================================================================================
    // Tableau de la liste (mode_list)
    //==================================================================================================================
    public static function get_table() : array {
        return TableBuilder
            ::new(Filiere::query())
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
                    ->label("Nom")
                    ->nom_colonne('nom')
                    ->render(RendersService::render_chaine)
                    ->triable()
                    ->cherchable()
            )
            ->default_tri('code')
            ->row_url(fn(Filiere $filiere) => route('filiere.detail', ['cle' => $filiere->cle]))
            ->get();
    }



    public static function get_filieres_pour_select() : array {
        return Filiere::list_pour_select('id', 'nom');
    }



    public static function process_update_or_create(?string $cle, array $attributes) : Filiere {
        return Filiere::update_by_cle_or_create($cle, $attributes);
    }



    public static function process_delete(string $cle) : void {
        $filiere = self::get_or_fail($cle);

        if (!$filiere->can_be_deleted()) {
            throw ValidationException::withMessages(
                [
                    'filiere' => "La filière {$filiere->code} ne peut pas être supprimée (elle contient des groupes ou des modules).",
                ]
            );
        }

        $filiere->delete();
    }



    public static function get_or_fail(string $cle) : Filiere {
        $filiere = Filiere::get_by_cle($cle);

        abort_if(!$filiere, 404, "Filière introuvable");

        return $filiere;
    }
}
