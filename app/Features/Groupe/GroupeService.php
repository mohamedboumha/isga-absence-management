<?php

namespace App\Features\Groupe;

use App\_Core\Builders\Table\TableBuilder;
use App\_Core\Builders\Table\TableColumn;
use App\_Core\Builders\Table\TableFilter;
use App\_Core\Services\RendersService;
use App\Features\AnneeUniversitaire\AnneeUniversitaireService;
use App\Features\Cycle\CycleService;
use App\Features\NiveauEtude\NiveauEtudeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class GroupeService {
    //==================================================================================================================
    // Tableau de la liste (mode_list) : le niveau en badge, à la couleur de sa filière
    //==================================================================================================================
    public static function get_table() : array {
        return TableBuilder
            ::new(
                Groupe
                    ::query()
                    ->with(['annee_universitaire', 'niveau_etude.cycle', 'niveau_etude.filiere.cycle'])
                    ->withCount('inscriptions')
            )
            ->add_column(
                TableColumn
                    ::new()
                    ->label("Groupe")
                    ->nom_colonne('nom')
                    ->render(RendersService::render_chaine)
                    ->triable()
                    ->cherchable()
            )
            ->add_column(
                TableColumn
                    ::new()
                    ->label("Niveau d'études")
                    ->nom_colonne('niveau_etude.code')
                    ->render(RendersService::render_badge)
                    ->couleur('niveau_etude.couleur_effective')
                    ->sous_texte('niveau_etude.cycle.nom')
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
                    ->label("Effectif")
                    ->nom_colonne('inscriptions_count')
                    ->render(RendersService::render_nombre)
                    ->triable()
            )
            //==========================================================================================================
            // Filtres
            //==========================================================================================================
            ->add_filtre(
                TableFilter
                    ::new('annee')
                    ->label("Année universitaire")
                    ->select(AnneeUniversitaireService::get_annees_pour_select())
                    ->sur_colonne('annee_universitaire_id')
            )
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
            'annees'  => AnneeUniversitaireService::get_annees_pour_select(),
            'niveaux' => NiveauEtudeService::get_niveaux_pour_select(),
        ];
    }



    //==================================================================================================================
    // Tous les groupes : "1AP-A (2026-2027)", année la plus récente en premier
    //==================================================================================================================
    public static function get_groupes_pour_select() : array {
        return Groupe
            ::query()
            ->with('annee_universitaire')
            ->join('annees_universitaires', 'annees_universitaires.id', '=', 'groupes.annee_universitaire_id')
            ->orderByDesc('annees_universitaires.date_debut')
            ->orderBy('groupes.nom')
            ->select('groupes.*')
            ->get()
            ->map(fn(Groupe $groupe) => [
                'valeur' => $groupe->id,
                'label'  => "{$groupe->nom} ({$groupe->annee_universitaire->libelle})",
            ])
            ->all();
    }



    //==================================================================================================================
    // Groupes de l'année active uniquement (inscription d'un étudiant)
    //==================================================================================================================
    public static function get_groupes_annee_active_pour_select() : array {
        $annee = AnneeUniversitaireService::get_active();

        if (!$annee) {
            return [];
        }

        return Groupe
            ::query()
            ->where('annee_universitaire_id', $annee->id)
            ->with('niveau_etude')
            ->orderBy('nom')
            ->get()
            ->map(fn(Groupe $groupe) => [
                'valeur' => $groupe->id,
                'label'  => "{$groupe->nom} — {$groupe->niveau_etude->libelle}",
            ])
            ->all();
    }



    public static function process_update_or_create(?string $cle, array $attributes) : Groupe {
        return Groupe::update_by_cle_or_create($cle, $attributes);
    }



    public static function process_delete(string $cle) : void {
        $groupe = self::get_or_fail($cle);

        if (!$groupe->can_be_deleted()) {
            throw ValidationException::withMessages([
                                                        'groupe' => "Le groupe {$groupe->nom} ne peut pas être supprimé : il a des inscriptions ou des séances.",
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
