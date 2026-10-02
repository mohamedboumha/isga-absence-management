<?php

namespace App\Features\Etudiant;

use App\_Core\Builders\Table\TableBuilder;
use App\_Core\Builders\Table\TableColumn;
use App\_Core\Builders\Table\TableFilter;
use App\_Core\Services\RendersService;
use App\Features\AnneeUniversitaire\AnneeUniversitaireService;
use App\Features\Cycle\CycleService;
use App\Features\Groupe\Groupe;
use App\Features\Groupe\GroupeService;
use App\Features\Inscription\InscriptionService;
use App\Features\NiveauEtude\NiveauEtudeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EtudiantService {
    const string filtre_non_inscrit = 'aucun';



    //==================================================================================================================
    // Tableau de la liste (mode_list) : l'étudiant (initiales, nom, CNE) et son groupe de l'année active en badge
    //==================================================================================================================
    public static function get_table() : array {
        return TableBuilder
            ::new(
                Etudiant
                    ::query()
                    ->with([
                               'inscription_active.groupe.niveau_etude.cycle',
                               'inscription_active.groupe.niveau_etude.filiere.cycle',
                           ])
            )
            ->add_column(
                TableColumn
                    ::new()
                    ->label("Étudiant")
                    ->nom_colonne('nom_complet')
                    ->render(RendersService::render_personne)
                    ->sous_texte('cne')
                    ->tri_sur('nom')
                    ->recherche_sur(['nom', 'prenom', 'cne'])
            )
            ->add_column(
                TableColumn
                    ::new()
                    ->label("Groupe (année active)")
                    ->nom_colonne('inscription_active.groupe.nom')
                    ->render(RendersService::render_badge)
                    ->couleur('inscription_active.groupe.niveau_etude.couleur_effective')
                    ->sous_texte('inscription_active.groupe.niveau_etude.libelle')
            )
            ->add_column(
                TableColumn
                    ::new()
                    ->label("E-mail")
                    ->nom_colonne('email')
                    ->render(RendersService::render_chaine)
                    ->triable()
                    ->cherchable()
            )
            ->add_column(
                TableColumn
                    ::new()
                    ->label("Téléphone")
                    ->nom_colonne('telephone')
                    ->render(RendersService::render_chaine)
            )
            //==========================================================================================================
            // Filtres : tous portent sur l'inscription de l'année active
            //==========================================================================================================
            ->add_filtre(
                TableFilter
                    ::new('groupe')
                    ->label("Groupe")
                    ->select([
                                 ['valeur' => self::filtre_non_inscrit, 'label' => "Non inscrit cette année"],
                                 ...GroupeService::get_groupes_annee_active_pour_select(),
                             ])
                    ->appliquer(fn(Builder $query, string $valeur) => $valeur === self::filtre_non_inscrit
                        ? $query->whereDoesntHave('inscription_active')
                        : $query->whereHas('inscription_active', fn(Builder $query) => $query->where('groupe_id', $valeur)))
            )
            ->add_filtre(
                TableFilter
                    ::new('niveau')
                    ->label("Niveau d'études")
                    ->select(NiveauEtudeService::get_niveaux_pour_select())
                    ->appliquer(fn(Builder $query, string $valeur) => $query->whereHas(
                        'inscription_active.groupe',
                        fn(Builder $query) => $query->where('niveau_etude_id', $valeur)
                    ))
            )
            ->add_filtre(
                TableFilter
                    ::new('cycle')
                    ->label("Cycle")
                    ->select(CycleService::get_cycles_pour_select())
                    ->appliquer(fn(Builder $query, string $valeur) => $query->whereHas(
                        'inscription_active.groupe.niveau_etude',
                        fn(Builder $query) => $query->where('cycle_id', $valeur)
                    ))
            )
            ->default_tri('nom_complet')
            ->row_url(fn(Etudiant $etudiant) => route('etudiant.detail', ['cle' => $etudiant->cle]))
            ->get();
    }



    //==================================================================================================================
    // Groupes de l'année active, avec une option "non inscrit"
    //==================================================================================================================
    public static function get_selects() : array {
        return [
            'groupes' => [
                ['valeur' => '', 'label' => "Non inscrit cette année"],
                ...GroupeService::get_groupes_annee_active_pour_select(),
            ],
        ];
    }



    //==================================================================================================================
    // Étudiant + inscription de l'année active, dans une seule transaction
    //==================================================================================================================
    public static function process_update_or_create(?string $cle, array $attributes) : Etudiant {
        $groupe_id = $attributes['groupe_id'] ?? null;
        unset($attributes['groupe_id']);

        return DB::transaction(function () use ($cle, $attributes, $groupe_id) {
            $etudiant = Etudiant::update_by_cle_or_create($cle, $attributes);

            //==========================================================================================================
            // Inscription de l'année active : création, changement de groupe ou retrait
            //==========================================================================================================
            $annee = AnneeUniversitaireService::get_active();

            if ($annee) {
                $groupe_id
                    ? InscriptionService::inscrire($etudiant, Groupe::findOrFail($groupe_id))
                    : InscriptionService::desinscrire($etudiant, $annee);
            }

            return $etudiant;
        });
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
