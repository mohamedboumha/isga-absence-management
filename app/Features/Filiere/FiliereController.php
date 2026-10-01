<?php

namespace App\Features\Filiere;

use App\Features\Cycle\CycleService;
use App\_Core\Services\RendersService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class FiliereController extends Controller {
    const string page_list   = 'filiere/filiere-list';
    const string page_detail = 'filiere/filiere-detail';

    const string route_list   = 'filieres.list';
    const string route_detail = 'filiere.detail';



    public function list() : InertiaResponse {
        return Inertia::render(self::page_list, [
            'mode_vue'    => RendersService::mode_list,
            'titre_page'  => "Filières",
            'breadcrumbs' => self::get_breadcrumbs(),
            'table'       => FiliereService::get_table(),
            'url_create'  => route(self::route_detail),
        ]);
    }



    public function detail(?string $cle = null, ?string $mode_detail = null) : InertiaResponse {
        $mode_vue = RendersService::get_mode_detail($cle, $mode_detail);

        $filiere = $cle
            ? FiliereService::get_or_fail($cle)
            : new Filiere();

        if ($mode_vue === RendersService::mode_edit && !$filiere->can_be_updated()) {
            abort(403, "Cette filière ne peut pas être modifiée.");
        }

        $titre_page = $filiere->exists
            ? "Filière {$filiere->code} — {$filiere->nom}"
            : "Nouvelle filière";

        return Inertia::render(self::page_detail, [
            'mode_vue'    => $mode_vue,
            'titre_page'  => $titre_page,
            'breadcrumbs' => self::get_breadcrumbs($titre_page, $filiere->exists ? route(self::route_detail, ['cle' => $filiere->cle]) : route(self::route_detail)),
            'item'        => self::item_to_array($filiere),
            'cycles'      => CycleService::get_cycles_pour_select(),
        ]);
    }



    public function update(FiliereRequest $request, ?string $cle = null) : RedirectResponse {
        $filiere = FiliereService::process_update_or_create($cle, $request->validated());

        return to_route(self::route_detail, ['cle' => $filiere->cle]);
    }



    public function delete(string $cle) : RedirectResponse {
        FiliereService::process_delete($cle);

        return to_route(self::route_list);
    }



    protected static function item_to_array(Filiere $filiere) : array {
        return [
            ...$filiere->toArray(),
            'can_be_deleted' => $filiere->exists && $filiere->can_be_deleted(),
        ];
    }



    protected static function get_breadcrumbs(?string $titre_detail = null, ?string $url_detail = null) : array {
        $breadcrumbs = [
            ['title' => "Filières", 'href' => route(self::route_list)],
        ];

        if ($titre_detail) {
            $breadcrumbs[] = ['title' => $titre_detail, 'href' => $url_detail];
        }

        return $breadcrumbs;
    }
}
