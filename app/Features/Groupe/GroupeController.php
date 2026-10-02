<?php

namespace App\Features\Groupe;

use App\_Core\Services\NotificationService;
use App\_Core\Services\RendersService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class GroupeController extends Controller {
    const string page_list   = 'groupe/groupe-list';
    const string page_detail = 'groupe/groupe-detail';

    const string route_list   = 'groupes.list';
    const string route_detail = 'groupe.detail';



    public function list() : InertiaResponse {
        return Inertia::render(self::page_list, [
            'mode_vue'    => RendersService::mode_list,
            'titre_page'  => "Groupes",
            'breadcrumbs' => self::get_breadcrumbs(),
            'table'       => GroupeService::get_table(),
            'url_create'  => route(self::route_detail),
        ]);
    }



    public function detail(?string $cle = null, ?string $mode_detail = null) : InertiaResponse {
        $mode_vue = RendersService::get_mode_detail($cle, $mode_detail);

        $groupe = $cle
            ? GroupeService::get_or_fail($cle)
            : GroupeService::create_new_item();

        if ($mode_vue === RendersService::mode_edit && !$groupe->can_be_updated()) {
            abort(403, "Ce groupe ne peut pas être modifié.");
        }

        $titre_page = $groupe->exists ? "Groupe {$groupe->nom}" : "Nouveau groupe";

        return Inertia::render(self::page_detail, [
            'mode_vue'     => $mode_vue,
            'titre_page'   => $titre_page,
            'breadcrumbs'  => self::get_breadcrumbs($titre_page, $groupe->exists ? route(self::route_detail, ['cle' => $groupe->cle]) : route(self::route_detail)),
            'item'         => self::item_to_array($groupe),
            'consultation' => $mode_vue === RendersService::mode_consultation ? GroupeConsultationService::get($groupe) : null,
            ...GroupeService::get_selects(),
        ]);
    }



    public function update(GroupeRequest $request, ?string $cle = null) : RedirectResponse {
        $groupe = GroupeService::process_update_or_create($cle, $request->validated());

        NotificationService::succes($cle ? "Groupe enregistré." : "Groupe créé.");

        return to_route(self::route_detail, ['cle' => $groupe->cle]);
    }



    public function delete(string $cle) : RedirectResponse {
        GroupeService::process_delete($cle);

        NotificationService::succes("Groupe supprimé.");

        return to_route(self::route_list);
    }



    protected static function item_to_array(Groupe $groupe) : array {
        return [
            ...$groupe->toArray(),
            'can_be_deleted' => $groupe->exists && $groupe->can_be_deleted(),
        ];
    }



    protected static function get_breadcrumbs(?string $titre_detail = null, ?string $url_detail = null) : array {
        $breadcrumbs = [
            ['title' => "Groupes", 'href' => route(self::route_list)],
        ];

        if ($titre_detail) {
            $breadcrumbs[] = ['title' => $titre_detail, 'href' => $url_detail];
        }

        return $breadcrumbs;
    }
}
