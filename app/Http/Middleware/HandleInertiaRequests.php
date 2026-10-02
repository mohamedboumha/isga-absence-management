<?php

namespace App\Http\Middleware;

use App\_Core\Navigation\SideBarService;
use App\_Core\Services\CouleurService;
use App\Features\AnneeUniversitaire\AnneeUniversitaireService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware {
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';



    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request) : ?string {
        return parent::version($request);
    }



    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request) : array {
        return [
            ...parent::share($request),
            'name'             => config('app.name'),
            'auth'             => [
                'user' => $request->user(),
            ],
            'sidebar'          => fn() => SideBarService::get_sidebar($request->user()),
            'palette_couleurs' => fn() => CouleurService::get_palette_pour_front(),

            //==========================================================================================================
            // Année en cours : affichée dans la barre du haut (seulement pour un utilisateur connecté)
            //==========================================================================================================
            'annee_active'     => fn() => $request->user() ? AnneeUniversitaireService::get_active()?->libelle : null,

            'sidebarOpen' => !$request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
