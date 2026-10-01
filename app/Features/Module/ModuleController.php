<?php

namespace App\Features\Module;

use App\_Core\Services\RendersService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ModuleController extends Controller {
    const string page_list   = 'module/module-list';
    const string page_detail = 'module/module-detail';

    const string route_list   = 'modules.list';
    const string route_detail = 'module.detail';



    public function list() : InertiaResponse {
        return Inertia::render(self::page_list, [
            'mode_vue'    => RendersService::mode_list,
            'titre_page'  => "Modules",
            'breadcrumbs' => self::get_breadcrumbs(),
            'table'       => ModuleService::get_table(),
            'url_create'  => route(self::route_detail),
        ]);
    }



    public function detail(?string $cle = null, ?string $mode_detail = null) : InertiaResponse {
        $mode_vue = RendersService::get_mode_detail($cle, $mode_detail);

        $module = $cle
            ? ModuleService::get_or_fail($cle)
            : new Module();

        if ($mode_vue === RendersService::mode_edit && !$module->can_be_updated()) {
            abort(403, "Ce module ne peut pas être modifié.");
        }

        $titre_page = $module->exists
            ? "Module {$module->code} — {$module->intitule}"
            : "Nouveau module";

        return Inertia::render(self::page_detail, [
            'mode_vue'    => $mode_vue,
            'titre_page'  => $titre_page,
            'breadcrumbs' => self::get_breadcrumbs($titre_page, $module->exists ? route(self::route_detail, ['cle' => $module->cle]) : route(self::route_detail)),
            'item'        => self::item_to_array($module),
            ...ModuleService::get_selects(),
        ]);
    }



    public function update(ModuleRequest $request, ?string $cle = null) : RedirectResponse {
        $module = ModuleService::process_update_or_create($cle, $request->validated());

        return to_route(self::route_detail, ['cle' => $module->cle]);
    }



    public function delete(string $cle) : RedirectResponse {
        ModuleService::process_delete($cle);

        return to_route(self::route_list);
    }



    protected static function item_to_array(Module $module) : array {
        return [
            ...$module->toArray(),
            'can_be_deleted' => $module->exists && $module->can_be_deleted(),
        ];
    }



    protected static function get_breadcrumbs(?string $titre_detail = null, ?string $url_detail = null) : array {
        $breadcrumbs = [
            ['title' => "Modules", 'href' => route(self::route_list)],
        ];

        if ($titre_detail) {
            $breadcrumbs[] = ['title' => $titre_detail, 'href' => $url_detail];
        }

        return $breadcrumbs;
    }
}
