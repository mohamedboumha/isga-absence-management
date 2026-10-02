<?php

namespace App\Features\Seance;

use App\_Core\Services\NotificationService;
use App\_Core\Services\RendersService;
use App\Features\AnneeUniversitaire\AnneeUniversitaireService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class SeanceController extends Controller {
    const string page_list   = 'seance/seance-list';
    const string page_detail = 'seance/seance-detail';

    const string route_list   = 'seances.list';
    const string route_detail = 'seance.detail';



    public function list() : InertiaResponse {
        return Inertia::render(self::page_list, [
            'mode_vue'    => RendersService::mode_list,
            'titre_page'  => "Séances",
            'breadcrumbs' => self::get_breadcrumbs(),
            'table'       => SeanceService::get_table(),
            'url_create'  => route(self::route_detail),
        ]);
    }



    public function detail(?string $cle = null, ?string $mode_detail = null) : InertiaResponse {
        $mode_vue = RendersService::get_mode_detail($cle, $mode_detail);

        $seance = $cle
            ? SeanceService::get_or_fail($cle)
            : SeanceService::create_new_item();

        if ($mode_vue === RendersService::mode_edit && !$seance->can_be_updated()) {
            abort(403, "Cette séance ne peut pas être modifiée.");
        }

        $titre_page = $seance->exists
            ? "Séance {$seance->module->code} — {$seance->groupe->nom} — {$seance->date?->format('d/m/Y')}"
            : "Nouvelle séance";


        //==============================================================================================================
        // Année en cours : limite les dates (et les groupes) proposés à la création d'une séance
        //==============================================================================================================
        $annee_active = AnneeUniversitaireService::get_active();

        return Inertia::render(self::page_detail, [
            'mode_vue'     => $mode_vue,
            'titre_page'   => $titre_page,
            'breadcrumbs'  => self::get_breadcrumbs($titre_page, $seance->exists ? route(self::route_detail, ['cle' => $seance->cle]) : route(self::route_detail)),
            'item'         => self::item_to_array($seance),
            'annee_active' => $annee_active ? [
                'id'         => $annee_active->id,
                'libelle'    => $annee_active->libelle,
                'date_debut' => $annee_active->date_debut?->format('Y-m-d'),
                'date_fin'   => $annee_active->date_fin?->format('Y-m-d'),
            ] : null,
            'consultation' => $mode_vue === RendersService::mode_consultation ? SeanceConsultationService::get($seance) : null,
            ...SeanceService::get_selects(),
        ]);
    }



    public function update(SeanceRequest $request, ?string $cle = null) : RedirectResponse {
        $seance = SeanceService::process_update_or_create($cle, $request->validated());

        NotificationService::succes($cle ? "Séance enregistrée." : "Séance planifiée.");

        return to_route(self::route_detail, ['cle' => $seance->cle]);
    }



    public function delete(string $cle) : RedirectResponse {
        SeanceService::process_delete($cle);

        NotificationService::succes("Séance supprimée.");

        return to_route(self::route_list);
    }



    protected static function item_to_array(Seance $seance) : array {
        return [
            ...$seance->toArray(),
            'can_be_deleted' => $seance->exists && $seance->can_be_deleted(),
        ];
    }



    protected static function get_breadcrumbs(?string $titre_detail = null, ?string $url_detail = null) : array {
        $breadcrumbs = [
            ['title' => "Séances", 'href' => route(self::route_list)],
        ];

        if ($titre_detail) {
            $breadcrumbs[] = ['title' => $titre_detail, 'href' => $url_detail];
        }

        return $breadcrumbs;
    }
}
