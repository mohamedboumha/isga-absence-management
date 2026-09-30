<?php

namespace App\Features\Appel;

use App\_Core\Services\RendersService;
use App\Features\Seance\Seance;
use App\Features\Seance\SeanceService;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use App\_Core\Services\NotificationService;

class AppelController extends Controller {
    const string page_detail      = 'appel/appel-detail';
    const string page_mes_seances = 'appel/mes-seances-list';


    //==================================================================================================================
    // Enseignant : ses séances
    //==================================================================================================================
    public function mes_seances(Request $request) : InertiaResponse {
        $enseignant = AppelService::get_enseignant_connecte($request->user());

        return Inertia::render(self::page_mes_seances, [
            'mode_vue'    => RendersService::mode_list,
            'titre_page'  => "Mes séances",
            'breadcrumbs' => [['title' => "Mes séances", 'href' => route('mes-seances.list')]],
            'table'       => AppelService::get_table_mes_seances($enseignant),
        ]);
    }



    //==================================================================================================================
    // Page d'appel : modification si autorisée, sinon consultation
    //==================================================================================================================
    public function detail(Request $request, string $cle) : InertiaResponse {
        /** @var User $user */
        $user   = $request->user();
        $seance = SeanceService::get_or_fail($cle);

        abort_if(!AppelService::can_voir($user, $seance), 403, "Vous n'avez pas accès à cette séance.");

        $refus = AppelService::get_refus_modification($user, $seance);

        return Inertia::render(self::page_detail, [
            'mode_vue'           => $refus ? RendersService::mode_consultation : RendersService::mode_edit,
            'titre_page'         => "Appel — {$seance->module->code} — {$seance->groupe->nom}",
            'breadcrumbs'        => self::get_breadcrumbs($user, $seance),
            'seance'             => AppelService::get_seance_infos($seance),
            'etudiants'          => AppelService::get_etudiants_appel($seance),
            'refus_modification' => $refus,
            'url_seance'         => AppelService::is_administration($user) ? route('seance.detail', ['cle' => $seance->cle]) : null,
        ]);
    }



    public function update(AppelRequest $request, string $cle) : RedirectResponse {
        /** @var User $user */
        $user   = $request->user();
        $seance = SeanceService::get_or_fail($cle);

        abort_if(!AppelService::can_voir($user, $seance), 403, "Vous n'avez pas accès à cette séance.");

        //==============================================================================================================
        // Même contrôle qu'à l'affichage : on ne fait jamais confiance au formulaire
        //==============================================================================================================
        $refus = AppelService::get_refus_modification($user, $seance);

        abort_if($refus !== null, 403, (string) $refus);

        $absences = $request->validated('absences') ?? [];

        AppelService::enregistrer_appel($seance, $absences, $user);

        NotificationService::succes(count($absences)
                                        ? "Appel enregistré : " . count($absences) . " absent(s)."
                                        : "Appel enregistré : tous présents.");

        return to_route('appel.detail', ['cle' => $seance->cle]);
    }



    protected static function get_breadcrumbs(User $user, Seance $seance) : array {
        $titre = "Appel {$seance->date?->format('d/m/Y')}";
        $url   = route('appel.detail', ['cle' => $seance->cle]);

        if (AppelService::is_administration($user)) {
            return [
                ['title' => "Séances", 'href' => route('seances.list')],
                ['title' => "Séance {$seance->module->code} — {$seance->groupe->nom}", 'href' => route('seance.detail', ['cle' => $seance->cle])],
                ['title' => $titre, 'href' => $url],
            ];
        }

        return [
            ['title' => "Mes séances", 'href' => route('mes-seances.list')],
            ['title' => $titre, 'href' => $url],
        ];
    }
}
