<?php

namespace App\Features\NiveauEtude;

use App\_Core\Services\NotificationService;
use App\_Core\Services\RendersService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class NiveauEtudeController extends Controller {
    const string page_list   = 'niveau-etude/niveau-etude-list';
    const string page_detail = 'niveau-etude/niveau-etude-detail';

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
            : new NiveauEtude(['annee_cycle' => 1, 'nb_semestres' => 2]);

        if ($mode_vue === RendersService::mode_edit && !$niveau->can_be_updated()) {
            abort(403, "Ce niveau ne peut pas être modifié.");
        }

        $titre_page = $niveau->exists ? "Niveau {$niveau->code}" : "Nouveau niveau d'études";

        return Inertia::render(self::page_detail, [
            'mode_vue'     => $mode_vue,
            'titre_page'   => $titre_page,
            'breadcrumbs'  => self::get_breadcrumbs($titre_page, $niveau->exists ? route(self::route_detail, ['cle' => $niveau->cle]) : route(self::route_detail)),
            'item'         => self::item_to_array($niveau),
            'consultation' => $mode_vue === RendersService::mode_consultation ? NiveauEtudeConsultationService::get($niveau) : null,
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



    protected static function item_to_array(NiveauEtude $niveau) : array {
        if (!$niveau->exists) {
            return [
                ...$niveau->toArray(),
                'couleur'            => null,
                'couleur_source'     => null,
                'suivants'           => [],
                'precedents'         => [],
                'est_derniere_annee' => false,
                'can_be_deleted'     => false,
            ];
        }

        $niveau->loadMissing(['cycle', 'filiere', 'suivants', 'precedents']);

        return [
            ...$niveau->toArray(),
            'couleur'            => $niveau->couleur_effective,
            'couleur_source'     => $niveau->filiere?->couleur ? "de la filière {$niveau->filiere->code}" : "du cycle {$niveau->cycle->code}",
            'suivants'           => $niveau->suivants->modelKeys(),
            'precedents'         => $niveau->precedents->pluck('code')
                                                       ->all(),
            'est_derniere_annee' => $niveau->est_derniere_annee,
            'can_be_deleted'     => $niveau->can_be_deleted(),
        ];
    }



    protected static function get_breadcrumbs(?string $titre_detail = null, ?string $url_detail = null) : array {
        $breadcrumbs = [
            ['title' => "Niveaux d'études", 'href' => route(self::route_list)],
        ];

        if ($titre_detail) {
            $breadcrumbs[] = ['title' => $titre_detail, 'href' => $url_detail];
        }

        return $breadcrumbs;
    }
}
