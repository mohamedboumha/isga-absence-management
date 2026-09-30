<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use App\_Core\Navigation\SideBarService;

class HandleInertiaRequests extends Middleware {
    protected $rootView = 'app';



    public function version(Request $request) : ?string {
        return parent::version($request);
    }



    public function share(Request $request) : array {
        return [
            ...parent::share($request),
            'name'        => config('app.name'),
            'auth'        => [
                'user' => $request->user(),
            ],
            'sidebar'     => fn() => SideBarService::get_sidebar($request->user()),
            'sidebarOpen' => !$request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'flash'       => [
                'lien_envoye' => fn() => $request->session()
                                                 ->get('lien_envoye'),
            ],
        ];
    }
}
