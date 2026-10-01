<?php

namespace App\Features\Seance;

use App\_Core\Builders\Table\TableBuilder;
use App\_Core\Builders\Table\TableColumn;
use App\_Core\Services\RendersService;
use App\Features\Enseignant\Enseignant;
use App\Features\Groupe\GroupeService;
use App\Features\Module\Module;
use Illuminate\Validation\ValidationException;

class SeanceService {
    //==================================================================================================================
    // Types de séance
    //==================================================================================================================
    const string type_cours = 'COURS';
    const string type_td    = 'TD';
    const string type_tp    = 'TP';

    const array types = [self::type_cours, self::type_td, self::type_tp];


    //==================================================================================================================
    // Créneaux habituels (utilisés par le faker)
    //==================================================================================================================
    const array creneaux = [
        ['08:30', '10:30'],
        ['10:45', '12:45'],
        ['14:00', '16:00'],
        ['16:15', '18:15'],
    ];


    //==================================================================================================================
    // Tableau de la liste (mode_list)
    //==================================================================================================================
    public static function get_table() : array {
        return TableBuilder
            ::new(Seance::query()
                        ->with(['module', 'enseignant', 'groupe']))
            ->add_column(
                TableColumn
                    ::new()
                    ->label("Date")
                    ->nom_colonne('date')
                    ->render(RendersService::render_date)
                    ->triable()
            )
            ->add_column(
                TableColumn
                    ::new()
                    ->label("Horaire")
                    ->nom_colonne('horaire_render')
                    ->render(RendersService::render_chaine)
            )
            ->add_column(
                TableColumn
                    ::new()
                    ->label("Module")
                    ->nom_colonne('module.code')
                    ->render(RendersService::render_chaine)
            )
            ->add_column(
                TableColumn
                    ::new()
                    ->label("Type")
                    ->nom_colonne('type')
                    ->render(RendersService::render_chaine)
                    ->triable()
            )
            ->add_column(
                TableColumn
                    ::new()
                    ->label("Groupe")
                    ->nom_colonne('groupe.nom')
                    ->render(RendersService::render_chaine)
            )
            ->add_column(
                TableColumn
                    ::new()
                    ->label("Enseignant")
                    ->nom_colonne('enseignant.nom_complet')
                    ->render(RendersService::render_chaine)
            )
            ->add_column(
                TableColumn
                    ::new()
                    ->label("Salle")
                    ->nom_colonne('salle')
                    ->render(RendersService::render_chaine)
                    ->cherchable()
            )
            ->add_column(
                TableColumn
                    ::new()
                    ->label("Statut")
                    ->nom_colonne('statut_render')
                    ->render(RendersService::render_chaine)
            )
            ->default_tri('date', 'desc')
            ->row_url(fn(Seance $seance) => route('seance.detail', ['cle' => $seance->cle]))
            ->get();
    }



    //==================================================================================================================
    // Nouvelle séance : date du jour par défaut
    //==================================================================================================================
    public static function create_new_item() : Seance {
        return new Seance([
                              'date'    => now()->format('Y-m-d'),
                              'type'    => self::type_cours,
                              'annulee' => false,
                          ]);
    }



    //==================================================================================================================
    // Listes pour les selects du formulaire
    //==================================================================================================================
    public static function get_selects() : array {
        return [
            //==========================================================================================================
            // Chaque module porte la liste de ses enseignants (pour filtrer le select Enseignant côté Vue)
            //==========================================================================================================
            'modules'     => Module
                ::query()
                ->with(['enseignants:id', 'niveau_etude'])
                ->orderBy('code')
                ->get()
                ->map(fn(Module $module) => [
                    'valeur'         => $module->id,
                    'label'          => "{$module->code} — {$module->intitule} ({$module->niveau_etude->code}, S{$module->semestre})",
                    'enseignant_ids' => $module->enseignants->pluck('id')->all(),
                ])
                ->all(),
            'enseignants' => Enseignant
                ::query()
                ->orderBy('nom')
                ->get()
                ->map(fn(Enseignant $enseignant) => [
                    'valeur' => $enseignant->id,
                    'label'  => $enseignant->nom_complet,
                ])
                ->all(),
            'groupes'     => GroupeService::get_groupes_pour_select(),
            'types'       => array_map(fn(string $type) => ['valeur' => $type, 'label' => $type], self::types),
        ];
    }



    //==================================================================================================================
    // Conflit d'horaire : une autre séance (non annulée) du même groupe ou du même enseignant qui chevauche
    //==================================================================================================================
    public static function get_seance_en_conflit(string $colonne, int $id, string $date, string $heure_debut, string $heure_fin, ?string $cle_ignoree) : ?Seance {
        return Seance
            ::query()
            ->non_annulees()
            ->where($colonne, $id)
            ->whereDate('date', $date)
            ->where('heure_debut', '<', $heure_fin)
            ->where('heure_fin', '>', $heure_debut)
            ->when($cle_ignoree, fn($query) => $query->where('cle', '!=', $cle_ignoree))
            ->first();
    }



    public static function process_update_or_create(?string $cle, array $attributes) : Seance {
        return Seance::update_by_cle_or_create($cle, $attributes);
    }



    public static function process_delete(string $cle) : void {
        $seance = self::get_or_fail($cle);

        if (!$seance->can_be_deleted()) {
            throw ValidationException::withMessages([
                                                        'seance' => "Cette séance ne peut pas être supprimée (l'appel a déjà été fait). Vous pouvez l'annuler.",
                                                    ]);
        }

        $seance->delete();
    }



    public static function get_or_fail(string $cle) : Seance {
        $seance = Seance::get_by_cle($cle);

        abort_if(!$seance, 404, "Séance introuvable");

        return $seance;
    }
}
