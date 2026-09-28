<?php

namespace App\Features\AnneeUniversitaire;

use App\_Core\Services\RendersService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class AnneeUniversitaireController extends Controller {
    //==================================================================================================================
    // Pages et routes du domaine
    //==================================================================================================================
    const string page_list   = 'annee-universitaire/annee-universitaire-list';
    const string page_detail = 'annee-universitaire/annee-universitaire-detail';

    const string route_list   = 'annees-universitaires.list';
    const string route_detail = 'annee-universitaire.detail';



    public function list() : InertiaResponse {
        return Inertia::render(self::page_list, [
            'mode_vue'    => RendersService::mode_list,
            'titre_page'  => "Années universitaires",
            'breadcrumbs' => self::get_breadcrumbs(),
            'table'       => AnneeUniversitaireService::get_table(),
            'url_create'  => route(self::route_detail),
        ]);
    }



    public function detail(?string $cle = null, ?string $mode_detail = null) : InertiaResponse {
        //==============================================================================================================
        // Mode : create, edit ou consultation
        //==============================================================================================================
        $mode_vue = RendersService::get_mode_detail($cle, $mode_detail);


        //==============================================================================================================
        // Récupération de l'année (ou nouvelle instance en création)
        //==============================================================================================================
        $annee = $cle
            ? AnneeUniversitaireService::get_or_fail($cle)
            : new AnneeUniversitaire(['active' => false]);

        if ($mode_vue === RendersService::mode_edit && !$annee->can_be_updated()) {
            abort(403, "Cette année universitaire ne peut pas être modifiée.");
        }

        $titre_page = $annee->exists ? "Année universitaire {$annee->libelle}" : "Nouvelle année universitaire";

        //==============================================================================================================
        // Render de la vue
        //==============================================================================================================
        return Inertia::render(self::page_detail, [
            'mode_vue'    => $mode_vue,
            'titre_page'  => $titre_page,
            'breadcrumbs' => self::get_breadcrumbs($titre_page, $annee->exists ? route(self::route_detail, ['cle' => $annee->cle]) : route(self::route_detail)),
            'item'        => self::item_to_array($annee),
        ]);
    }



    public function update(AnneeUniversitaireRequest $request, ?string $cle = null) : RedirectResponse {
        $annee = AnneeUniversitaireService::process_update_or_create($cle, $request->validated());

        return to_route(self::route_detail, ['cle' => $annee->cle]);
    }



    public function delete(string $cle) : RedirectResponse {
        AnneeUniversitaireService::process_delete($cle);

        return to_route(self::route_list);
    }



    protected static function item_to_array(AnneeUniversitaire $annee) : array {
        return [
            ...$annee->toArray(),
            'periode_render' => $annee->exists ? $annee->periode_render : null,
            'can_be_deleted' => $annee->exists && $annee->can_be_deleted(),
        ];
    }



    protected static function get_breadcrumbs(?string $titre_detail = null, ?string $url_detail = null) : array {
        $breadcrumbs = [
            ['title' => "Années universitaires", 'href' => route(self::route_list)],
        ];

        if ($titre_detail) {
            $breadcrumbs[] = ['title' => $titre_detail, 'href' => $url_detail];
        }

        return $breadcrumbs;
    }
}
