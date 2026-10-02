<?php

namespace App\Features\Etudiant;

use App\_Core\Services\NotificationService;
use App\_Core\Services\RendersService;
use App\Features\AnneeUniversitaire\AnneeUniversitaireService;
use App\Features\Inscription\InscriptionService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class EtudiantController extends Controller {
    const string page_list   = 'etudiant/etudiant-list';
    const string page_detail = 'etudiant/etudiant-detail';

    const string route_list   = 'etudiants.list';
    const string route_detail = 'etudiant.detail';



    public function list() : InertiaResponse {
        return Inertia::render(self::page_list, [
            'mode_vue'    => RendersService::mode_list,
            'titre_page'  => "Étudiants",
            'breadcrumbs' => self::get_breadcrumbs(),
            'table'       => EtudiantService::get_table(),
            'url_create'  => route(self::route_detail),
        ]);
    }



    public function detail(?string $cle = null, ?string $mode_detail = null) : InertiaResponse {
        $mode_vue = RendersService::get_mode_detail($cle, $mode_detail);

        $etudiant = $cle
            ? EtudiantService::get_or_fail($cle)
            : new Etudiant();

        if ($mode_vue === RendersService::mode_edit && !$etudiant->can_be_updated()) {
            abort(403, "Cet étudiant ne peut pas être modifié.");
        }

        $titre_page = $etudiant->exists ? $etudiant->nom_complet : "Nouvel étudiant";

        return Inertia::render(self::page_detail, [
            'mode_vue'     => $mode_vue,
            'titre_page'   => $titre_page,
            'breadcrumbs'  => self::get_breadcrumbs($titre_page, $etudiant->exists ? route(self::route_detail, ['cle' => $etudiant->cle]) : route(self::route_detail)),
            'item'         => self::item_to_array($etudiant),
            'annee_active' => AnneeUniversitaireService::get_active()?->libelle,
            'historique'   => $etudiant->exists ? InscriptionService::get_historique($etudiant) : [],
            'consultation' => $mode_vue === RendersService::mode_consultation ? EtudiantConsultationService::get($etudiant) : null,
            ...EtudiantService::get_selects(),
        ]);
    }



    public function update(EtudiantRequest $request, ?string $cle = null) : RedirectResponse {
        $etudiant = EtudiantService::process_update_or_create($cle, $request->validated());

        NotificationService::succes($cle ? "Étudiant enregistré." : "Étudiant créé.");

        return to_route(self::route_detail, ['cle' => $etudiant->cle]);
    }



    public function delete(string $cle) : RedirectResponse {
        EtudiantService::process_delete($cle);

        NotificationService::succes("Étudiant supprimé.");

        return to_route(self::route_list);
    }



    protected static function item_to_array(Etudiant $etudiant) : array {
        return [
            ...$etudiant->toArray(),
            'groupe_id'      => $etudiant->inscription_active?->groupe_id,
            'can_be_deleted' => $etudiant->exists && $etudiant->can_be_deleted(),
        ];
    }



    protected static function get_breadcrumbs(?string $titre_detail = null, ?string $url_detail = null) : array {
        $breadcrumbs = [
            ['title' => "Étudiants", 'href' => route(self::route_list)],
        ];

        if ($titre_detail) {
            $breadcrumbs[] = ['title' => $titre_detail, 'href' => $url_detail];
        }

        return $breadcrumbs;
    }
}
