<?php

namespace App\Features\Export;

use App\Features\Appel\AppelService;
use App\Features\Etudiant\EtudiantService;
use App\Features\Groupe\GroupeService;
use App\Features\Seance\SeanceService;
use App\Http\Controllers\Controller;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ExportController extends Controller {
    public function releve_etudiant(Request $request, string $cle) : Response {
        $etudiant = EtudiantService::get_or_fail($cle);
        $periode  = self::get_periode($request);

        return Pdf
            ::loadView('pdf.releve-etudiant', ExportService::get_releve_etudiant($etudiant, $periode))
            ->setPaper('a4')
            ->stream("releve-absences-{$etudiant->cne}.pdf");
    }



    public function rapport_groupe(Request $request, string $cle) : Response {
        $groupe  = GroupeService::get_or_fail($cle);
        $periode = self::get_periode($request);

        return Pdf
            ::loadView('pdf.rapport-groupe', ExportService::get_rapport_groupe($groupe, $periode))
            ->setPaper('a4')
            ->stream("rapport-absences-{$groupe->nom}.pdf");
    }



    public function feuille_presence(Request $request, string $cle) : Response {
        /** @var User $user */
        $user   = $request->user();
        $seance = SeanceService::get_or_fail($cle);

        //==============================================================================================================
        // Administration, ou l'enseignant de la séance (RG-12)
        //==============================================================================================================
        abort_if(!AppelService::can_voir($user, $seance), 403, "Vous n'avez pas accès à cette séance.");

        return Pdf
            ::loadView('pdf.feuille-presence', ExportService::get_feuille_presence($seance))
            ->setPaper('a4')
            ->stream("feuille-presence-{$seance->groupe->nom}-{$seance->date?->format('Y-m-d')}.pdf");
    }



    protected static function get_periode(Request $request) : array {
        $donnees = $request->validate([
                                          'date_debut' => ['nullable', 'date'],
                                          'date_fin'   => ['nullable', 'date', 'after_or_equal:date_debut'],
                                      ]);

        return ExportService::get_periode($donnees['date_debut'] ?? null, $donnees['date_fin'] ?? null);
    }
}
