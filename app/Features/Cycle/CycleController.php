<?php

namespace App\Features\Cycle;

use App\_Core\Services\NotificationService;
use App\_Core\Services\RendersService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class CycleController extends Controller {
    const string page_list   = 'cycle/cycle-list';
    const string page_detail = 'cycle/cycle-detail';

    const string route_list   = 'cycles.list';
    const string route_detail = 'cycle.detail';



    public function list() : InertiaResponse {
        return Inertia::render(self::page_list, [
            'mode_vue'    => RendersService::mode_list,
            'titre_page'  => "Cycles",
            'breadcrumbs' => self::get_breadcrumbs(),
            'table'       => CycleService::get_table(),
            'url_create'  => route(self::route_detail),
        ]);
    }



    public function detail(?string $cle = null, ?string $mode_detail = null) : InertiaResponse {
        $mode_vue = RendersService::get_mode_detail($cle, $mode_detail);

        $cycle = $cle
            ? CycleService::get_or_fail($cle)
            : new Cycle();

        if ($mode_vue === RendersService::mode_edit && !$cycle->can_be_updated()) {
            abort(403, "Ce cycle ne peut pas être modifié.");
        }

        $titre_page = $cycle->exists ? $cycle->nom : "Nouveau cycle";

        return Inertia::render(self::page_detail, [
            'mode_vue'     => $mode_vue,
            'titre_page'   => $titre_page,
            'breadcrumbs'  => self::get_breadcrumbs($titre_page, $cycle->exists ? route(self::route_detail, ['cle' => $cycle->cle]) : route(self::route_detail)),
            'item'         => self::item_to_array($cycle),
            'consultation' => $mode_vue === RendersService::mode_consultation ? CycleConsultationService::get($cycle) : null,
        ]);
    }



    public function update(CycleRequest $request, ?string $cle = null) : RedirectResponse {
        $cycle = CycleService::process_update_or_create($cle, $request->validated());

        NotificationService::succes($cle ? "Cycle enregistré." : "Cycle créé.");

        return to_route(self::route_detail, ['cle' => $cycle->cle]);
    }



    public function delete(string $cle) : RedirectResponse {
        CycleService::process_delete($cle);

        NotificationService::succes("Cycle supprimé.");

        return to_route(self::route_list);
    }



    protected static function item_to_array(Cycle $cycle) : array {
        return [
            ...$cycle->toArray(),
            'can_be_deleted' => $cycle->exists && $cycle->can_be_deleted(),
        ];
    }



    protected static function get_breadcrumbs(?string $titre_detail = null, ?string $url_detail = null) : array {
        $breadcrumbs = [
            ['title' => "Cycles", 'href' => route(self::route_list)],
        ];

        if ($titre_detail) {
            $breadcrumbs[] = ['title' => $titre_detail, 'href' => $url_detail];
        }

        return $breadcrumbs;
    }
}
