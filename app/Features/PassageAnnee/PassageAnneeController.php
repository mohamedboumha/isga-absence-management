<?php

namespace App\Features\PassageAnnee;

use App\_Core\Services\NotificationService;
use App\Features\AnneeUniversitaire\AnneeUniversitaire;
use App\Features\AnneeUniversitaire\AnneeUniversitaireService;
use App\Features\Groupe\Groupe;
use App\Features\Inscription\InscriptionService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class PassageAnneeController extends Controller {
    const string page_index  = 'passage-annee/passage-annee-index';
    const string route_index = 'passage-annee.index';

    //==================================================================================================================
    // Libellés du récapitulatif ("22 admis, 2 redoublant(s)...")
    //==================================================================================================================
    const array libelles_recap = [
        InscriptionService::decision_admis      => "admis",
        InscriptionService::decision_redoublant => "redoublant(s)",
        InscriptionService::decision_diplome    => "diplômé(s)",
        InscriptionService::decision_sortant    => "sortant(s)",
    ];



    public function index(Request $request) : InertiaResponse {
        //==============================================================================================================
        // Année source : celle demandée, sinon l'année active, sinon la plus récente
        //==============================================================================================================
        $source = AnneeUniversitaire::get_by_cle($request->query('source'))
            ?? AnneeUniversitaireService::get_active()
            ?? AnneeUniversitaire::query()
                                 ->orderByDesc('date_debut')
                                 ->first();


        //==============================================================================================================
        // Année cible : celle demandée (si postérieure), sinon la suivante
        //==============================================================================================================
        $cible = AnneeUniversitaire::get_by_cle($request->query('cible'));

        if ($source && (!$cible || $cible->date_debut?->lte($source->date_debut))) {
            $cible = PassageAnneeService::get_annee_cible_par_defaut($source);
        }


        //==============================================================================================================
        // Groupe choisi (uniquement s'il appartient à l'année source)
        //==============================================================================================================
        $groupe = Groupe::get_by_cle($request->query('groupe'));

        if ($groupe && $groupe->annee_universitaire_id !== $source?->id) {
            $groupe = null;
        }

        return Inertia::render(self::page_index, [
            'titre_page'     => "Passage d'année",
            'breadcrumbs'    => [['title' => "Passage d'année", 'href' => route(self::route_index)]],
            'annees'         => PassageAnneeService::get_annees_pour_select(),
            'source_cle'     => $source?->cle,
            'source_libelle' => $source?->libelle,
            'cible_cle'      => $cible?->cle,
            'cible_id'       => $cible?->id,
            'cible_libelle'  => $cible?->libelle,
            'groupes'        => $source ? PassageAnneeService::get_groupes_source($source) : [],
            'detail'         => $groupe && $cible ? PassageAnneeService::get_detail_groupe($groupe, $cible) : null,
            'decisions'      => InscriptionService::decisions,
        ]);
    }



    public function executer(PassageAnneeRequest $request) : RedirectResponse {
        $groupe = Groupe::with('annee_universitaire')
                        ->findOrFail((int) $request->validated('groupe_id'));
        $cible  = AnneeUniversitaire::findOrFail((int) $request->validated('annee_cible_id'));

        $compteurs = PassageAnneeService::executer(
            $groupe,
            $cible,
            $request->validated('destinations') ?? [],
            $request->validated('etudiants')
        );


        //==============================================================================================================
        // "1AP-A : 22 admis, 2 redoublant(s), 1 sortant(s)."
        //==============================================================================================================
        $recap = collect($compteurs)
            ->filter()
            ->map(fn(int $nombre, string $decision) => "$nombre " . self::libelles_recap[$decision])
            ->join(', ');

        NotificationService::succes("Passage enregistré pour {$groupe->nom} : $recap.");

        return to_route(self::route_index, [
            'source' => $groupe->annee_universitaire->cle,
            'cible'  => $cible->cle,
            'groupe' => $groupe->cle,
        ]);
    }
}
