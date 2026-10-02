<?php

namespace App\Features\User;

use App\_Core\Builders\Table\TableBuilder;
use App\_Core\Builders\Table\TableColumn;
use App\_Core\Services\EmailService;
use App\_Core\Services\RendersService;
use App\Models\User;
use Illuminate\Support\Str;

class UserService {
    //==================================================================================================================
    // Rôles
    //==================================================================================================================
    const string role_super_admin = 'super_admin';
    const string role_admin       = 'admin';
    const string role_enseignant  = 'enseignant';

    const array roles_administration = [
        self::role_super_admin,
        self::role_admin,
    ];

    const array roles_tous = [
        self::role_super_admin,
        self::role_admin,
        self::role_enseignant,
    ];

    const array roles_labels = [
        self::role_super_admin => "Super-administrateur",
        self::role_admin       => "Administrateur",
        self::role_enseignant  => "Enseignant",
    ];


    //==================================================================================================================
    // Tableau de la liste (mode_list) : la personne (initiales, nom, e-mail), son rôle, l'état du compte
    //==================================================================================================================
    public static function get_table() : array {
        return TableBuilder
            ::new(User::query())
            ->add_column(
                TableColumn
                    ::new()
                    ->label("Utilisateur")
                    ->nom_colonne('nom_complet')
                    ->render(RendersService::render_personne)
                    ->valeur(fn(User $user) => trim("{$user->prenom} " . mb_strtoupper((string) $user->name)))
                    ->sous_texte('email')
                    ->tri_sur('name')
                    ->recherche_sur(['name', 'prenom', 'email'])
            )
            ->add_column(
                TableColumn
                    ::new()
                    ->label("Rôle")
                    ->nom_colonne('role_render')
                    ->render(RendersService::render_chaine)
                    ->tri_sur('role')
            )
            ->add_column(
                TableColumn
                    ::new()
                    ->label("Compte")
                    ->nom_colonne('compte')
                    ->render(RendersService::render_statut)
                    ->valeur(fn(User $user) => $user->actif ? 'actif' : 'desactive')
                    ->tri_sur('actif')
            )
            ->default_tri('nom_complet')
            ->row_url(fn(User $user) => route('utilisateur.detail', ['cle' => $user->cle]))
            ->get();
    }



    //==================================================================================================================
    // Rôles proposés à la création / modification (les enseignants se créent depuis la feature Enseignant)
    //==================================================================================================================
    public static function get_roles_pour_select(bool $inclure_enseignant = false) : array {
        $roles = $inclure_enseignant ? self::roles_tous : self::roles_administration;

        return array_map(fn(string $role) => ['valeur' => $role, 'label' => self::roles_labels[$role]], $roles);
    }



    public static function is_enseignant(User $user) : bool {
        return $user->role === self::role_enseignant;
    }



    //==================================================================================================================
    // Nombre de super-administrateurs actifs, en excluant éventuellement un compte
    //==================================================================================================================
    public static function nb_super_admins_actifs(?int $sauf_id = null) : int {
        return User
            ::query()
            ->where('role', self::role_super_admin)
            ->where('actif', true)
            ->when($sauf_id, fn($query) => $query->whereKeyNot($sauf_id))
            ->count();
    }



    //==================================================================================================================
    // Création / modification (l'e-mail du nouveau compte est envoyé par le controller, après l'enregistrement)
    //==================================================================================================================
    public static function process_update_or_create(?string $cle, array $attributes) : User {
        $user = $cle ? self::get_or_fail($cle) : new User();

        //==============================================================================================================
        // Compte enseignant : seul "actif" se modifie ici (le reste suit la fiche enseignant)
        //==============================================================================================================
        if ($user->exists && self::is_enseignant($user)) {
            $user->forceFill(['actif' => $attributes['actif']])
                 ->save();

            return $user;
        }

        $user->forceFill([
                             'name'   => $attributes['name'],
                             'prenom' => $attributes['prenom'],
                             'email'  => $attributes['email'],
                             'role'   => $attributes['role'],
                             'actif'  => $attributes['actif'],
                         ]);

        if (!$user->exists) {
            $user->forceFill([
                                 'password'          => Str::random(40), // provisoire : la personne choisit le sien via le lien
                                 'email_verified_at' => now(),
                             ]);
        }

        $user->save();

        return $user;
    }



    public static function envoyer_lien_mot_de_passe(User $user) : string {
        return EmailService::envoyer_lien_mot_de_passe($user->email);
    }



    public static function get_or_fail(string $cle) : User {
        $user = User::query()
                    ->where('cle', $cle)
                    ->first();

        abort_if(!$user, 404, "Utilisateur introuvable");

        return $user;
    }
}
