<?php

namespace App\_Core\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware {
    //==================================================================================================================
    // Dans les routes :  ->middleware(RoleMiddleware::roles(UserService::roles_administration))
    // produit           :  "role:super_admin,admin"
    //==================================================================================================================
    public static function roles(array $roles) : string {
        return 'role:' . implode(',', $roles);
    }



    public function handle(Request $request, Closure $next, string ...$roles) : Response {
        $user = $request->user();

        if (!$user || !in_array($user->role, $roles, true)) {
            abort(403, "Vous n'avez pas accès à cette page.");
        }

        return $next($request);
    }
}
