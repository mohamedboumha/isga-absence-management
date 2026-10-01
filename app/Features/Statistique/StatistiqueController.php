<?php

namespace App\Features\Statistique;

use App\Features\Cycle\CycleService;
use App\Features\Groupe\GroupeService;
use App\Features\Module\Module;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Maatwebsite\Excel\Facades\Excel;
use App\Features\NiveauEtude\NiveauEtudeService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StatistiqueController extends Controller {
    const string page_index = 'statistique/statistique-index';



    public function index(Request $request) : InertiaResponse {
        $filtres = self::get_filtres($request);

        return Inertia::render(self::page_index, [
            'titre_page'    => "Statistiques",
            'breadcrumbs'   => [['title' => "Statistiques", 'href' => route('statistiques.index')]],
            'filtres'       => $filtres,
            'resume'        => StatistiqueService::get_resume($filtres),
            'par_groupe'    => StatistiqueService::get_par_groupe($filtres),
            'par_module'    => StatistiqueService::get_par_module($filtres),
            'top_etudiants' => StatistiqueService::get_top_etudiants($filtres),
            'evolution'     => StatistiqueService::get_evolution_par_semaine($filtres),
            'selects'       => [
                'cycles'  => [['valeur' => '', 'label' => "Tous"], ...CycleService::get_cycles_pour_select()],
                'niveaux' => [['valeur' => '', 'label' => "Tous"], ...NiveauEtudeService::get_niveaux_pour_select()],
                'groupes' => [['valeur' => '', 'label' => "Tous"], ...GroupeService::get_groupes_pour_select()],
                'modules' => [
                    ['valeur' => '', 'label' => "Tous"],
                    ...Module::query()
                             ->orderBy('code')
                             ->get()
                             ->map(fn(Module $module) => ['valeur' => $module->id, 'label' => "{$module->code} — {$module->intitule}"])
                             ->all(),
                ],
            ],
        ]);
    }



    protected static function get_filtres(Request $request) : array {
        $donnees = $request->validate([
                                          'date_debut'      => ['nullable', 'date'],
                                          'date_fin'        => ['nullable', 'date', 'after_or_equal:date_debut'],
                                          'cycle_id'        => ['nullable', 'integer'],
                                          'niveau_etude_id' => ['nullable', 'integer'],
                                          'groupe_id'       => ['nullable', 'integer'],
                                          'module_id'       => ['nullable', 'integer'],
                                      ]);

        return [
            ...StatistiqueService::get_filtres_par_defaut(),
            ...array_filter($donnees, fn($valeur) => $valeur !== null),
        ];
    }



    public function export(Request $request) : BinaryFileResponse {
        $filtres = self::get_filtres($request);

        return Excel::download(new StatistiquesExport($filtres), 'statistiques-' . now()->format('Y-m-d') . '.xlsx');
    }
}
