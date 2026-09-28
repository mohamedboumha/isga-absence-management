<?php

namespace App\_Core\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ActifMiddleware {
    public function handle(Request $request, Closure $next) : Response {
        $user = $request->user();

        //==============================================================================================================
        // Compte désactivé pendant la session : déconnexion immédiate
        //==============================================================================================================
        if ($user && !$user->actif) {
            Auth::guard('web')
                ->logout();

            $request->session()
                    ->invalidate();
            $request->session()
                    ->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors(['email' => "Ce compte est désactivé. Contactez le super-administrateur."]);
        }

        return $next($request);
    }
}
