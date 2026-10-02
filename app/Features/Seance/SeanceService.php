<?php

namespace App\Features\Seance;

use App\_Core\Builders\Table\TableBuilder;
use App\_Core\Builders\Table\TableColumn;
use App\_Core\Builders\Table\TableFilter;
use App\_Core\Services\RendersService;
use App\Features\Enseignant\Enseignant;
use App\Features\Groupe\Groupe;
use App\Features\Groupe\GroupeService;
use App\Features\Module\Module;
use Illuminate\Database\Eloquent\Builder;
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
    // Créneaux habituels
    //==================================================================================================================
    const array creneaux = [
        ['08:30', '10:30'],
        ['10:45', '12:45'],
        ['14:00', '16:00'],
        ['16:15', '18:15'],
    ];


    //==================================================================================================================
    // Statuts calculés (mêmes clés que Seance::statut_cle et statuts.ts)
    //==================================================================================================================
    const array statuts = [
        'appel_a_faire' => "Appel à faire",
        'appel_fait'    => "Appel fait",
        'planifiee'     => "Planifiée",
        'annulee'       => "Annulée",
    ];



    //==================================================================================================================
    // Tableau de la liste (mode_list) : le module en badge, le statut en pastille
    //==================================================================================================================
    public static function get_table() : array {
        return TableBuilder
            ::new(
                Seance
                    ::query()
                    ->with(['module.niveau_etude.cycle', 'module.niveau_etude.filiere.cycle', 'enseignant', 'groupe'])
            )
            ->add_column(
                TableColumn
                    ::new()
                    ->label("Date")
                    ->nom_colonne('date')
                    ->render(RendersService::render_date)
                    ->sous_texte('horaire_render')
                    ->triable()
            )
            ->add_column(
                TableColumn
                    ::new()
                    ->label("Module")
                    ->nom_colonne('module.code')
                    ->render(RendersService::render_badge)
                    ->couleur('module.niveau_etude.couleur_effective')
                    ->sous_texte('module.intitule')
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
                    ->nom_colonne('statut_cle')
                    ->render(RendersService::render_statut)
            )
            //==========================================================================================================
            // Filtres
            //==========================================================================================================
            ->add_filtre(
                TableFilter
                    ::new('periode')
                    ->label("Période")
                    ->periode()
                    ->sur_colonne('date')
            )
            ->add_filtre(
                TableFilter
                    ::new('statut')
                    ->label("Statut")
                    ->select(array_map(fn(string $cle, string $label) => ['valeur' => $cle, 'label' => $label], array_keys(self::statuts), self::statuts))
                    ->appliquer(fn(Builder $query, string $valeur) => self::filtrer_par_statut($query, $valeur))
            )
            ->add_filtre(
                TableFilter
                    ::new('groupe')
                    ->label("Groupe")
                    ->select(GroupeService::get_groupes_pour_select())
                    ->sur_colonne('groupe_id')
            )
            ->add_filtre(
                TableFilter
                    ::new('enseignant')
                    ->label("Enseignant")
                    ->select(self::get_enseignants_pour_select())
                    ->sur_colonne('enseignant_id')
            )
            ->add_filtre(
                TableFilter
                    ::new('type')
                    ->label("Type")
                    ->select(array_map(fn(string $type) => ['valeur' => $type, 'label' => $type], self::types))
                    ->sur_colonne('type')
            )
            ->default_tri('date', 'desc')
            ->row_url(fn(Seance $seance) => route('seance.detail', ['cle' => $seance->cle]))
            ->get();
    }



    //==================================================================================================================
    // Même logique que Seance::statut_cle, traduite en SQL
    //==================================================================================================================
    protected static function filtrer_par_statut(Builder $query, string $statut) : void {
        match ($statut) {
            'annulee'       => $query->where('annulee', true),
            'appel_fait'    => $query->where('annulee', false)->whereNotNull('appel_fait_le'),
            'appel_a_faire' => $query->where('annulee', false)->whereNull('appel_fait_le')->whereDate('date', '<=', today()),
            'planifiee'     => $query->where('annulee', false)->whereNull('appel_fait_le')->whereDate('date', '>', today()),
            default         => null,
        };
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



    public static function get_enseignants_pour_select() : array {
        return Enseignant
            ::query()
            ->orderBy('nom')
            ->get()
            ->map(fn(Enseignant $enseignant) => [
                'valeur' => $enseignant->id,
                'label'  => $enseignant->nom_complet,
            ])
            ->all();
    }



    //==================================================================================================================
    // Listes pour les selects du formulaire
    // Groupe → Module → Enseignant : chaque option porte de quoi filtrer la liste suivante côté Vue
    //==================================================================================================================
    public static function get_selects() : array {
        return [
            //==========================================================================================================
            // Groupes (année la plus récente en premier), avec leur année et leur niveau d'études
            //==========================================================================================================
            'groupes'     => Groupe
                ::query()
                ->with('annee_universitaire')
                ->join('annees_universitaires', 'annees_universitaires.id', '=', 'groupes.annee_universitaire_id')
                ->orderByDesc('annees_universitaires.date_debut')
                ->orderBy('groupes.nom')
                ->select('groupes.*')
                ->get()
                ->map(fn(Groupe $groupe) => [
                    'valeur'                 => $groupe->id,
                    'label'                  => "{$groupe->nom} ({$groupe->annee_universitaire->libelle})",
                    'annee_universitaire_id' => $groupe->annee_universitaire_id,
                    'niveau_etude_id'        => $groupe->niveau_etude_id,
                    'date_debut'             => $groupe->annee_universitaire->date_debut?->format('Y-m-d'),
                    'date_fin'               => $groupe->annee_universitaire->date_fin?->format('Y-m-d'),
                ])
                ->all(),

            //==========================================================================================================
            // Modules, avec leur niveau d'études (filtre par groupe) et leurs enseignants (filtre de l'enseignant)
            //==========================================================================================================
            'modules'     => Module
                ::query()
                ->with(['enseignants:id', 'niveau_etude'])
                ->orderBy('code')
                ->get()
                ->map(fn(Module $module) => [
                    'valeur'          => $module->id,
                    'label'           => "{$module->code} — {$module->intitule} (S{$module->semestre})",
                    'niveau_etude_id' => $module->niveau_etude_id,
                    'enseignant_ids'  => $module->enseignants->pluck('id')->all(),
                ])
                ->all(),

            'enseignants' => self::get_enseignants_pour_select(),
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
