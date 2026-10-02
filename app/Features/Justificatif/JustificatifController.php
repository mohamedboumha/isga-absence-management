<?php

namespace App\Features\Justificatif;

use App\_Core\Services\NotificationService;
use App\_Core\Services\RendersService;
use App\Features\Absence\Absence;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class JustificatifController extends Controller {
    const string page_list   = 'justificatif/justificatif-list';
    const string page_detail = 'justificatif/justificatif-detail';

    const string route_list   = 'justificatifs.list';
    const string route_detail = 'justificatif.detail';



    public function list(Request $request) : InertiaResponse {
        $statut = $request->query('statut', JustificatifService::statut_en_attente);

        return Inertia::render(self::page_list, [
            'mode_vue'    => RendersService::mode_list,
            'titre_page'  => "Justificatifs",
            'breadcrumbs' => self::get_breadcrumbs(),
            'table'       => JustificatifService::get_table($statut),
            'statut'      => $statut,
            'statuts'     => JustificatifService::statuts,
            'compteurs'   => JustificatifService::get_compteurs(),
            'url_create'  => route(self::route_detail),
        ]);
    }



    public function detail(?string $cle = null, ?string $mode_detail = null) : InertiaResponse {
        $mode_vue = RendersService::get_mode_detail($cle, $mode_detail);

        $justificatif = $cle
            ? JustificatifService::get_or_fail($cle)
            : new Justificatif();

        if ($mode_vue === RendersService::mode_edit && !$justificatif->can_be_updated()) {
            abort(403, "Un justificatif déjà traité ne peut plus être modifié.");
        }

        $titre_page = $justificatif->exists
            ? "Justificatif — {$justificatif->etudiant->nom_complet}"
            : "Nouveau justificatif";

        return Inertia::render(self::page_detail, [
            'mode_vue'    => $mode_vue,
            'titre_page'  => $titre_page,
            'breadcrumbs' => self::get_breadcrumbs($titre_page, $justificatif->exists ? route(self::route_detail, ['cle' => $justificatif->cle]) : route(self::route_detail)),
            'item'        => self::item_to_array($justificatif),
            ...JustificatifService::get_selects(),
        ]);
    }



    public function update(JustificatifRequest $request, ?string $cle = null) : RedirectResponse {
        if ($cle) {
            abort_if(!JustificatifService::get_or_fail($cle)
                                         ->can_be_updated(), 403, "Un justificatif déjà traité ne peut plus être modifié.");
        }

        $justificatif = JustificatifService::process_update_or_create($cle, $request->validated(), $request->file('fichier'));

        NotificationService::succes($cle ? "Justificatif enregistré." : "Justificatif ajouté.");

        return to_route(self::route_detail, ['cle' => $justificatif->cle]);
    }



    public function valider(Request $request, string $cle) : RedirectResponse {
        /** @var User $user */
        $user = $request->user();

        $nb_absences = JustificatifService::valider(JustificatifService::get_or_fail($cle), $user);

        NotificationService::succes("Justificatif validé : {$nb_absences} absence(s) justifiée(s).");

        return to_route(self::route_detail, ['cle' => $cle]);
    }



    public function refuser(Request $request, string $cle) : RedirectResponse {
        $donnees = $request->validate(
            ['motif_refus' => ['required', 'string', 'max:500']],
            ['motif_refus.required' => "Le motif du refus est obligatoire (RG-06)."]
        );

        /** @var User $user */
        $user = $request->user();

        JustificatifService::refuser(JustificatifService::get_or_fail($cle), $donnees['motif_refus'], $user);

        NotificationService::succes("Justificatif refusé.");

        return to_route(self::route_detail, ['cle' => $cle]);
    }



    //==================================================================================================================
    // Le document n'est accessible que par cette route (connexion + rôle vérifiés)
    // ?telecharger=1 : téléchargement ; sinon : affichage dans la visionneuse
    //==================================================================================================================
    public function fichier(Request $request, string $cle) : StreamedResponse {
        $justificatif = JustificatifService::get_or_fail($cle);
        $disque       = Storage::disk(JustificatifService::disque);

        abort_if(!$justificatif->fichier_chemin || !$disque->exists($justificatif->fichier_chemin), 404, "Document introuvable");

        return $request->boolean('telecharger')
            ? $disque->download($justificatif->fichier_chemin, $justificatif->fichier_nom)
            : $disque->response($justificatif->fichier_chemin, $justificatif->fichier_nom);
    }



    public function delete(string $cle) : RedirectResponse {
        JustificatifService::process_delete($cle);

        NotificationService::succes("Justificatif supprimé.");

        return to_route(self::route_list);
    }



    protected static function item_to_array(Justificatif $justificatif) : array {
        //==============================================================================================================
        // Absences concernées : couvertes par la période (en attente) ou déjà liées (traité)
        //==============================================================================================================
        $absences = $justificatif->exists ? $justificatif->get_absences_couvertes() : collect();

        return [
            ...$justificatif->toArray(),
            'statut_render'  => $justificatif->exists ? $justificatif->statut_render : null,
            'hors_delai'     => $justificatif->hors_delai,
            'fichier_url'    => $justificatif->fichier_chemin ? route('justificatif.fichier', ['cle' => $justificatif->cle]) : null,
            'traite_par_nom' => $justificatif->traite_par_user?->name,
            'traite_le'      => $justificatif->traite_le?->format('d/m/Y à H:i'),
            'absences'       => $absences->map(fn(Absence $absence) => [
                'date'      => $absence->seance->date?->format('d/m/Y'),
                'horaire'   => $absence->seance->horaire_render,
                'module'    => $absence->seance->module->code,
                'justifiee' => $absence->justifiee,
            ])
                                         ->values()
                                         ->all(),
            'can_be_updated' => $justificatif->exists && $justificatif->can_be_updated(),
            'can_be_deleted' => $justificatif->exists && $justificatif->can_be_deleted(),
        ];
    }



    protected static function get_breadcrumbs(?string $titre_detail = null, ?string $url_detail = null) : array {
        $breadcrumbs = [
            ['title' => "Justificatifs", 'href' => route(self::route_list)],
        ];

        if ($titre_detail) {
            $breadcrumbs[] = ['title' => $titre_detail, 'href' => $url_detail];
        }

        return $breadcrumbs;
    }
}
