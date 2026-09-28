<?php

namespace App\Features\Semestre;

use App\_Core\Services\RendersService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class SemestreController extends Controller {
    const string page_list   = 'semestre/semestre-list';
    const string page_detail = 'semestre/semestre-detail';

    const string route_list   = 'semestres.list';
    const string route_detail = 'semestre.detail';



    public function list() : InertiaResponse {
        return Inertia::render(self::page_list, [
            'mode_vue'    => RendersService::mode_list,
            'titre_page'  => "Semestres",
            'breadcrumbs' => self::get_breadcrumbs(),
            'table'       => SemestreService::get_table(),
            'url_create'  => route(self::route_detail),
        ]);
    }



    public function detail(?string $cle = null, ?string $mode_detail = null) : InertiaResponse {
        $mode_vue = RendersService::get_mode_detail($cle, $mode_detail);

        $semestre = $cle
            ? SemestreService::get_or_fail($cle)
            : new Semestre();

        if ($mode_vue === RendersService::mode_edit && !$semestre->can_be_updated()) {
            abort(403, "Ce semestre ne peut pas être modifié.");
        }

        $titre_page = $semestre->exists
            ? "Semestre {$semestre->libelle} — {$semestre->annee_universitaire->libelle}"
            : "Nouveau semestre";

        return Inertia::render(self::page_detail, [
            'mode_vue'    => $mode_vue,
            'titre_page'  => $titre_page,
            'breadcrumbs' => self::get_breadcrumbs($titre_page, $semestre->exists ? route(self::route_detail, ['cle' => $semestre->cle]) : route(self::route_detail)),
            'item'        => self::item_to_array($semestre),
            'annees'      => SemestreService::get_annees_pour_select(),
        ]);
    }



    public function update(SemestreRequest $request, ?string $cle = null) : RedirectResponse {
        $semestre = SemestreService::process_update_or_create($cle, $request->validated());

        return to_route(self::route_detail, ['cle' => $semestre->cle]);
    }



    public function delete(string $cle) : RedirectResponse {
        SemestreService::process_delete($cle);

        return to_route(self::route_list);
    }



    protected static function item_to_array(Semestre $semestre) : array {
        return [
            ...$semestre->toArray(),
            'can_be_deleted' => $semestre->exists && $semestre->can_be_deleted(),
        ];
    }



    protected static function get_breadcrumbs(?string $titre_detail = null, ?string $url_detail = null) : array {
        $breadcrumbs = [
            ['title' => "Semestres", 'href' => route(self::route_list)],
        ];

        if ($titre_detail) {
            $breadcrumbs[] = ['title' => $titre_detail, 'href' => $url_detail];
        }

        return $breadcrumbs;
    }
}
