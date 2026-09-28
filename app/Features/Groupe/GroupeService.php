<?php

namespace App\Features\Groupe;

use App\_Core\Builders\Table\TableBuilder;
use App\_Core\Builders\Table\TableColumn;
use App\_Core\Services\RendersService;
use App\Features\AnneeUniversitaire\AnneeUniversitaireService;
use App\Features\Filiere\FiliereService;
use App\Features\Semestre\SemestreService;
use Illuminate\Validation\ValidationException;

class GroupeService {
    //==================================================================================================================
    // Tableau de la liste (mode_list)
    //==================================================================================================================
    public static function get_table() : array {
        return TableBuilder
            ::new(Groupe::query()
                        ->with(['annee_universitaire', 'filiere']))
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
                    ->label("Filière")
                    ->nom_colonne('filiere.nom')
                    ->render(RendersService::render_chaine)
            )
            ->add_column(
                TableColumn
                    ::new()
                    ->label("Niveau")
                    ->nom_colonne('niveau')
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
            ->default_tri('nom')
            ->row_url(fn(Groupe $groupe) => route('groupe.detail', ['cle' => $groupe->cle]))
            ->get();
    }



    //==================================================================================================================
    // Nouveau groupe : pré-rempli avec l'année active
    //==================================================================================================================
    public static function create_new_item() : Groupe {
        return new Groupe([
                              'annee_universitaire_id' => AnneeUniversitaireService::get_active()?->id,
                          ]);
    }



    //==================================================================================================================
    // Listes pour les selects du formulaire
    //==================================================================================================================
    public static function get_selects() : array {
        return [
            'annees'   => SemestreService::get_annees_pour_select(),
            'filieres' => FiliereService::get_filieres_pour_select(),
            'niveaux'  => FiliereService::get_niveaux_pour_select(),
        ];
    }



    public static function process_update_or_create(?string $cle, array $attributes) : Groupe {
        return Groupe::update_by_cle_or_create($cle, $attributes);
    }



    public static function process_delete(string $cle) : void {
        $groupe = self::get_or_fail($cle);

        if (!$groupe->can_be_deleted()) {
            throw ValidationException::withMessages([
                                                        'groupe' => "Le groupe {$groupe->nom} ne peut pas être supprimé (il contient des étudiants ou des séances).",
                                                    ]);
        }

        $groupe->delete();
    }



    public static function get_or_fail(string $cle) : Groupe {
        $groupe = Groupe::get_by_cle($cle);

        abort_if(!$groupe, 404, "Groupe introuvable");

        return $groupe;
    }
}
