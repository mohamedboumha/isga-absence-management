<?php

namespace App\Features\Journal;

use App\_Core\Builders\Table\TableBuilder;
use App\_Core\Builders\Table\TableColumn;
use App\_Core\Services\RendersService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class JournalService {
    //==================================================================================================================
    // Permet de suspendre le journal (ex. génération des données de démonstration)
    //==================================================================================================================
    public static bool $actif = true;


    //==================================================================================================================
    // Actions
    //==================================================================================================================
    const string action_creation     = 'CREATION';
    const string action_modification = 'MODIFICATION';
    const string action_suppression  = 'SUPPRESSION';
    const string action_restauration = 'RESTAURATION';

    const array actions = [
        self::action_creation     => "Création",
        self::action_modification => "Modification",
        self::action_suppression  => "Suppression",
        self::action_restauration => "Restauration",
    ];


    //==================================================================================================================
    // Nom des classes => libellé affiché
    //==================================================================================================================
    const array entites = [
        'AnneeUniversitaire' => "Année universitaire",
        'Cycle'              => "Cycle",
        'Filiere'            => "Filière",
        'NiveauEtude'        => "Niveau d'études",
        'Groupe'             => "Groupe",
        'Module'             => "Module",
        'Enseignant'         => "Enseignant",
        'Etudiant'           => "Étudiant",
        'Inscription'        => "Inscription",
        'Seance'             => "Séance",
        'Absence'            => "Absence",
        'Justificatif'       => "Justificatif",
        'User'               => "Utilisateur",
    ];


    //==================================================================================================================
    // Champs jamais enregistrés (secrets, champs techniques)
    //==================================================================================================================
    const array champs_ignores = [
        'id',
        'cle',
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'fichier_chemin',
        'created_at',
        'updated_at',
        'deleted_at',
    ];


    //==================================================================================================================
    // Écriture d'une entrée
    //==================================================================================================================
    public static function enregistrer(Model $model, string $action, ?array $anciennes_valeurs, ?array $nouvelles_valeurs) : void {
        if (!self::$actif) {
            return;
        }

        $journal = new JournalAction();

        $journal->forceFill([
                                'user_id'           => Auth::id(),
                                'action'            => $action,
                                'entite'            => class_basename($model),
                                'entite_id'         => $model->getKey(),
                                'entite_cle'        => $model->getAttribute('cle'),
                                'entite_label'      => self::get_label($model),
                                'anciennes_valeurs' => $anciennes_valeurs,
                                'nouvelles_valeurs' => $nouvelles_valeurs,
                                'ip'                => app()->runningInConsole() ? null : request()->ip(),
                            ]);

        $journal->save();
    }



    public static function filtrer(array $valeurs) : array {
        return array_diff_key($valeurs, array_flip(self::champs_ignores));
    }



    protected static function get_label(Model $model) : ?string {
        if (method_exists($model, 'get_label')) {
            return mb_substr((string) $model->get_label(), 0, 255);
        }

        return $model->getAttribute('name') ?? "#{$model->getKey()}";
    }



    //==================================================================================================================
    // Tableau de la liste (mode_list) : le journal est immuable, le menu "⋯" ne propose que Consulter
    //==================================================================================================================
    public static function get_table() : array {
        return TableBuilder
            ::new(JournalAction::query()
                               ->with('user'))
            ->add_column(
                TableColumn
                    ::new()
                    ->label("Date")
                    ->nom_colonne('created_at')
                    ->render(RendersService::render_chaine)
                    ->valeur(fn(JournalAction $journal) => $journal->created_at?->format('d/m/Y à H:i'))
                    ->triable()
            )
            ->add_column(
                TableColumn
                    ::new()
                    ->label("Utilisateur")
                    ->nom_colonne('auteur_render')
                    ->render(RendersService::render_chaine)
            )
            ->add_column(
                TableColumn
                    ::new()
                    ->label("Action")
                    ->nom_colonne('action_render')
                    ->render(RendersService::render_chaine)
            )
            ->add_column(
                TableColumn
                    ::new()
                    ->label("Élément")
                    ->nom_colonne('entite_label')
                    ->render(RendersService::render_chaine)
                    ->sous_texte('entite_render')
                    ->recherche_sur(['entite_label', 'entite'])
            )
            ->default_tri('created_at', 'desc')
            ->row_url(fn(JournalAction $journal) => route('journal.detail', ['cle' => $journal->cle]))
            ->actions_consultation_seule()
            ->get();
    }



    //==================================================================================================================
    // Détail : une ligne par champ modifié (ancienne valeur → nouvelle valeur)
    //==================================================================================================================
    public static function get_changements(JournalAction $journal) : array {
        $anciennes = $journal->anciennes_valeurs ?? [];
        $nouvelles = $journal->nouvelles_valeurs ?? [];

        return collect(array_unique([...array_keys($anciennes), ...array_keys($nouvelles)]))
            ->map(fn(string $champ) => [
                'champ'    => $champ,
                'ancienne' => $anciennes[$champ] ?? null,
                'nouvelle' => $nouvelles[$champ] ?? null,
            ])
            ->values()
            ->all();
    }



    public static function get_or_fail(string $cle) : JournalAction {
        $journal = JournalAction::query()
                                ->where('cle', $cle)
                                ->first();

        abort_if(!$journal, 404, "Entrée du journal introuvable");

        return $journal;
    }
}
