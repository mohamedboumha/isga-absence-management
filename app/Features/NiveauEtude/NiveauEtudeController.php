<?php

namespace App\Features\NiveauEtude;

use App\_Core\Services\NotificationService;
use App\_Core\Services\RendersService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class NiveauEtudeController extends Controller {
    const string page_list    = 'niveau-etude/niveau-etude-list';
    const string page_detail  = 'niveau-etude/niveau-etude-detail';
    const string route_list   = 'niveaux-etudes.list';
    const string route_detail = 'niveau-etude.detail';



    public function list() : InertiaResponse {
        return Inertia::render(self::page_list, [
            'mode_vue'    => RendersService::mode_list,
            'titre_page'  => "Niveaux d'études",
            'breadcrumbs' => self::get_breadcrumbs(),
            'table'       => NiveauEtudeService::get_table(),
            'url_create'  => route(self::route_detail),
        ]);
    }



    public function detail(?string $cle = null, ?string $mode_detail = null) : InertiaResponse {
        $mode_vue = RendersService::get_mode_detail($cle, $mode_detail);

        $niveau = $cle
            ? NiveauEtudeService::get_or_fail($cle)
                                ->load(['cycle', 'suivants', 'precedents'])
            : new NiveauEtude(['nb_semestres' => 2, 'annee_cycle' => 1]);

        $titre_page = $niveau->exists ? "{$niveau->code} — {$niveau->libelle}" : "Nouveau niveau d'études";

        return Inertia::render(self::page_detail, [
            'mode_vue'    => $mode_vue,
            'titre_page'  => $titre_page,
            'breadcrumbs' => self::get_breadcrumbs($titre_page, $niveau->exists ? route(self::route_detail, ['cle' => $niveau->cle]) : route(self::route_detail)),
            'item'        => [
                ...$niveau->toArray(),
                'suivants'           => $niveau->exists ? $niveau->suivants->pluck('id')
                                                                           ->all() : [],
                'precedents'         => $niveau->exists ? $niveau->precedents->pluck('code')
                                                                             ->all() : [],
                'est_derniere_annee' => $niveau->exists && $niveau->est_derniere_annee,
                'can_be_deleted'     => $niveau->exists && $niveau->can_be_deleted(),
            ],
            ...NiveauEtudeService::get_selects(),
        ]);
    }



    public function update(NiveauEtudeRequest $request, ?string $cle = null) : RedirectResponse {
        $niveau = NiveauEtudeService::process_update_or_create($cle, $request->validated());

        NotificationService::succes($cle ? "Niveau d'études enregistré." : "Niveau d'études créé.");

        return to_route(self::route_detail, ['cle' => $niveau->cle]);
    }



    public function delete(string $cle) : RedirectResponse {
        NiveauEtudeService::process_delete($cle);

        NotificationService::succes("Niveau d'études supprimé.");

        return to_route(self::route_list);
    }



    protected static function get_breadcrumbs(?string $titre_detail = null, ?string $url_detail = null) : array {
        $breadcrumbs = [['title' => "Niveaux d'études", 'href' => route(self::route_list)]];

        if ($titre_detail) {
            $breadcrumbs[] = ['title' => $titre_detail, 'href' => $url_detail];
        }

        return $breadcrumbs;
    }
}
