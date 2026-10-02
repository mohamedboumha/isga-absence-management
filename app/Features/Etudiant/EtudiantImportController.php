<?php

namespace App\Features\Etudiant;

use App\_Core\Services\NotificationService;
use App\Features\AnneeUniversitaire\AnneeUniversitaireService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EtudiantImportController extends Controller {
    const string page        = 'etudiant/etudiant-import';
    const string route_page  = 'etudiants.import';
    const string cle_session = 'import_etudiants';


    //==================================================================================================================
    // Page d'import : étape 1 (fichier) ou étape 2 (aperçu de l'analyse, gardée en session)
    //==================================================================================================================
    public function page(Request $request) : InertiaResponse {
        $analyse = $request->session()
                           ->get(self::cle_session);

        return Inertia::render(self::page, [
            'titre_page'   => "Importer des étudiants",
            'breadcrumbs'  => [
                ['title' => "Étudiants", 'href' => route('etudiants.list')],
                ['title' => "Importer", 'href' => route(self::route_page)],
            ],
            'annee_active' => AnneeUniversitaireService::get_active()?->libelle,
            'analyse'      => $analyse ? [
                'jeton'     => $analyse['jeton'],
                'fichier'   => $analyse['fichier'],
                'annee'     => $analyse['annee'],
                'compteurs' => $analyse['compteurs'],
                'apercu'    => $analyse['apercu'],
            ] : null,
        ]);
    }



    public function modele() : BinaryFileResponse {
        return Excel::download(new EtudiantImportModele(), 'modele-import-etudiants.xlsx');
    }



    //==================================================================================================================
    // Analyse du fichier : rien n'est enregistré, le résultat est gardé en session pour l'aperçu
    //==================================================================================================================
    public function analyser(Request $request) : RedirectResponse {
        $donnees = $request->validate(
            ['fichier' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120']],
            [
                'fichier.required' => "Choisissez un fichier Excel.",
                'fichier.mimes'    => "Le fichier doit être un fichier Excel (.xlsx, .xls) ou CSV.",
                'fichier.max'      => "Le fichier ne doit pas dépasser 5 Mo.",
            ]
        );

        $request->session()
                ->put(self::cle_session, EtudiantImportService::analyser($donnees['fichier']));

        return to_route(self::route_page);
    }



    //==================================================================================================================
    // Confirmation : import des lignes valides de l'analyse en cours
    //==================================================================================================================
    public function confirmer(Request $request) : RedirectResponse {
        $analyse = $request->session()
                           ->get(self::cle_session);

        if (!$analyse || $request->input('jeton') !== $analyse['jeton']) {
            NotificationService::erreur("Cette analyse n'est plus valable. Importez le fichier à nouveau.");

            return to_route(self::route_page);
        }

        if (!$analyse['a_importer']) {
            NotificationService::info("Aucun étudiant à importer : toutes les lignes sont inchangées ou en erreur.");

            return to_route(self::route_page);
        }

        $bilan = EtudiantImportService::importer($analyse['a_importer']);

        $request->session()
                ->forget(self::cle_session);

        NotificationService::succes("Import terminé : {$bilan['crees']} étudiant(s) créé(s), {$bilan['modifies']} mis à jour.");

        return to_route('etudiants.list');
    }



    public function annuler(Request $request) : RedirectResponse {
        $request->session()
                ->forget(self::cle_session);

        return to_route(self::route_page);
    }
}
