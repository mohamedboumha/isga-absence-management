<?php

namespace App\Features\Enseignant;

use App\_Core\Services\NotificationService;
use App\_Core\Services\RendersService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class EnseignantController extends Controller {
    const string page_list   = 'enseignant/enseignant-list';
    const string page_detail = 'enseignant/enseignant-detail';

    const string route_list   = 'enseignants.list';
    const string route_detail = 'enseignant.detail';



    public function list() : InertiaResponse {
        return Inertia::render(self::page_list, [
            'mode_vue'    => RendersService::mode_list,
            'titre_page'  => "Enseignants",
            'breadcrumbs' => self::get_breadcrumbs(),
            'table'       => EnseignantService::get_table(),
            'url_create'  => route(self::route_detail),
        ]);
    }



    public function detail(?string $cle = null, ?string $mode_detail = null) : InertiaResponse {
        $mode_vue = RendersService::get_mode_detail($cle, $mode_detail);

        $enseignant = $cle
            ? EnseignantService::get_or_fail($cle)
            : new Enseignant();

        if ($mode_vue === RendersService::mode_edit && !$enseignant->can_be_updated()) {
            abort(403, "Cet enseignant ne peut pas être modifié.");
        }

        $titre_page = $enseignant->exists ? $enseignant->nom_complet : "Nouvel enseignant";

        return Inertia::render(self::page_detail, [
            'mode_vue'     => $mode_vue,
            'titre_page'   => $titre_page,
            'breadcrumbs'  => self::get_breadcrumbs($titre_page, $enseignant->exists ? route(self::route_detail, ['cle' => $enseignant->cle]) : route(self::route_detail)),
            'item'         => self::item_to_array($enseignant),
            'modules'      => EnseignantService::get_modules_pour_select(),
            'consultation' => $mode_vue === RendersService::mode_consultation ? EnseignantConsultationService::get($enseignant) : null,
        ]);
    }



    public function update(EnseignantRequest $request, ?string $cle = null) : RedirectResponse {
        $enseignant = EnseignantService::process_update_or_create($cle, $request->validated());

        NotificationService::succes($cle
                                        ? "Enseignant enregistré."
                                        : "Enseignant créé. Un lien pour choisir son mot de passe lui a été envoyé.");

        return to_route(self::route_detail, ['cle' => $enseignant->cle]);
    }



    public function delete(string $cle) : RedirectResponse {
        EnseignantService::process_delete($cle);

        NotificationService::succes("Enseignant supprimé. Son compte est désactivé.");

        return to_route(self::route_list);
    }



    protected static function item_to_array(Enseignant $enseignant) : array {
        return [
            ...$enseignant->toArray(),
            'modules'        => $enseignant->exists ? $enseignant->modules()->pluck('modules.id')->all() : [],
            'compte_actif'   => $enseignant->user?->actif ?? false,
            'can_be_deleted' => $enseignant->exists && $enseignant->can_be_deleted(),
        ];
    }



    protected static function get_breadcrumbs(?string $titre_detail = null, ?string $url_detail = null) : array {
        $breadcrumbs = [
            ['title' => "Enseignants", 'href' => route(self::route_list)],
        ];

        if ($titre_detail) {
            $breadcrumbs[] = ['title' => $titre_detail, 'href' => $url_detail];
        }

        return $breadcrumbs;
    }
}
