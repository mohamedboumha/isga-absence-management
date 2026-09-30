<?php

namespace App\Features\Dashboard;

use App\Features\Appel\AppelService;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class DashboardController extends Controller {
    public function index(Request $request) : InertiaResponse {
        /** @var User $user */
        $user        = $request->user();
        $breadcrumbs = [['title' => "Tableau de bord", 'href' => route('dashboard')]];

        //==============================================================================================================
        // Un tableau de bord par profil
        //==============================================================================================================
        if (AppelService::is_administration($user)) {
            return Inertia::render('dashboard/dashboard-administration', [
                'titre_page'  => "Tableau de bord",
                'breadcrumbs' => $breadcrumbs,
                ...DashboardService::get_donnees_administration(),
            ]);
        }

        return Inertia::render('dashboard/dashboard-enseignant', [
            'titre_page'  => "Tableau de bord",
            'breadcrumbs' => $breadcrumbs,
            'prenom'      => $user->prenom,
            ...DashboardService::get_donnees_enseignant(AppelService::get_enseignant_connecte($user)),
        ]);
    }
}
