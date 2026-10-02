<?php

namespace App\Features\User;

use App\_Core\Services\EmailService;
use App\_Core\Services\NotificationService;
use App\_Core\Services\RendersService;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class UserController extends Controller {
    const string page_list   = 'utilisateur/utilisateur-list';
    const string page_detail = 'utilisateur/utilisateur-detail';

    const string route_list   = 'utilisateurs.list';
    const string route_detail = 'utilisateur.detail';



    public function list() : InertiaResponse {
        return Inertia::render(self::page_list, [
            'mode_vue'    => RendersService::mode_list,
            'titre_page'  => "Utilisateurs",
            'breadcrumbs' => self::get_breadcrumbs(),
            'table'       => UserService::get_table(),
            'url_create'  => route(self::route_detail),
        ]);
    }



    public function detail(Request $request, ?string $cle = null, ?string $mode_detail = null) : InertiaResponse {
        $mode_vue = RendersService::get_mode_detail($cle, $mode_detail);

        $user = $cle
            ? UserService::get_or_fail($cle)
            : new User(['role' => UserService::role_admin, 'actif' => true]);

        $titre_page = $user->exists
            ? trim("{$user->prenom} " . mb_strtoupper((string) $user->name))
            : "Nouvel administrateur";

        return Inertia::render(self::page_detail, [
            'mode_vue'    => $mode_vue,
            'titre_page'  => $titre_page,
            'breadcrumbs' => self::get_breadcrumbs($titre_page, $user->exists ? route(self::route_detail, ['cle' => $user->cle]) : route(self::route_detail)),
            'item'        => self::item_to_array($user, $request->user()),
            'roles'       => UserService::get_roles_pour_select(inclure_enseignant: $user->exists && UserService::is_enseignant($user)),
        ]);
    }



    public function update(UserRequest $request, ?string $cle = null) : RedirectResponse {
        $user = UserService::process_update_or_create($cle, $request->validated());

        //==============================================================================================================
        // Nouveau compte : le lien "choisir mon mot de passe" ; le compte est créé même si l'envoi échoue
        //==============================================================================================================
        if (!$cle) {
            $statut = UserService::envoyer_lien_mot_de_passe($user);

            $statut === EmailService::echec
                ? NotificationService::avertissement("Compte créé, mais l'e-mail n'a pas pu être envoyé à {$user->email}. Utilisez « Renvoyer le lien » un peu plus tard.")
                : NotificationService::succes("Compte créé. Un lien pour choisir son mot de passe a été envoyé à {$user->email}.");
        } else {
            NotificationService::succes("Compte enregistré.");
        }

        return to_route(self::route_detail, ['cle' => $user->cle]);
    }



    public function lien_mot_de_passe(string $cle) : RedirectResponse {
        $user = UserService::get_or_fail($cle);

        match (UserService::envoyer_lien_mot_de_passe($user)) {
            EmailService::envoye => NotificationService::succes("Lien envoyé à {$user->email}."),
            EmailService::limite => NotificationService::info("Un lien a déjà été envoyé à {$user->email} il y a moins d'une minute. Patientez avant d'en demander un autre."),
            default              => NotificationService::erreur("L'e-mail n'a pas pu être envoyé à {$user->email}. Réessayez dans quelques minutes."),
        };

        return to_route(self::route_detail, ['cle' => $cle]);
    }



    protected static function item_to_array(User $user, ?User $moi) : array {
        return [
            'cle'            => $user->cle,
            'name'           => $user->name,
            'prenom'         => $user->prenom,
            'email'          => $user->email,
            'role'           => $user->role,
            'actif'          => (bool) $user->actif,
            'is_enseignant'  => $user->exists && UserService::is_enseignant($user),
            'is_moi'         => $user->exists && $user->id === $moi?->id,
            'url_enseignant' => $user->enseignant ? route('enseignant.detail', ['cle' => $user->enseignant->cle]) : null,
            'cree_le'        => $user->created_at?->format('d/m/Y'),
        ];
    }



    protected static function get_breadcrumbs(?string $titre_detail = null, ?string $url_detail = null) : array {
        $breadcrumbs = [
            ['title' => "Utilisateurs", 'href' => route(self::route_list)],
        ];

        if ($titre_detail) {
            $breadcrumbs[] = ['title' => $titre_detail, 'href' => $url_detail];
        }

        return $breadcrumbs;
    }
}
