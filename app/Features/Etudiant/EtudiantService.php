<?php

namespace App\Features\Etudiant;

use App\_Core\Builders\Table\TableBuilder;
use App\_Core\Builders\Table\TableColumn;
use App\_Core\Services\RendersService;
use App\Features\Groupe\GroupeService;
use Illuminate\Validation\ValidationException;
use App\Features\AnneeUniversitaire\AnneeUniversitaireService;
use App\Features\Groupe\Groupe;
use App\Features\Inscription\InscriptionService;
use Illuminate\Support\Facades\DB;

class EtudiantService {
    //==================================================================================================================
    // Tableau de la liste (mode_list)
    //==================================================================================================================
    public static function get_table() : array {
        return TableBuilder
            ::new(Etudiant::query()
                          ->with(['inscription_active.groupe.niveau_etude']))
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
                    ->label("Groupe (année active)")
                    ->nom_colonne('inscription_active.groupe.nom')
                    ->render(RendersService::render_chaine)
            )
            ->add_column(
                TableColumn
                    ::new()
                    ->label("Niveau")
                    ->nom_colonne('inscription_active.groupe.niveau_etude.code')
                    ->render(RendersService::render_chaine)
            )
            ->default_tri('nom')
            ->row_url(fn(Etudiant $etudiant) => route('etudiant.detail', ['cle' => $etudiant->cle]))
            ->get();
    }



    public static function get_selects() : array {
        return [
            'groupes' => [
                ['valeur' => '', 'label' => "Non inscrit cette année"],
                ...GroupeService::get_groupes_annee_active_pour_select(),
            ],
        ];
    }



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
