<?php

namespace App\Features\Statistique;

use App\_Core\Builders\Table\TableFilter;
use App\Features\AnneeUniversitaire\AnneeUniversitaireService;
use App\Features\Cycle\CycleService;
use App\Features\Groupe\GroupeService;
use App\Features\Module\Module;
use App\Features\NiveauEtude\NiveauEtudeService;
use App\Features\Seance\SeanceService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class StatistiqueController extends Controller {
    const string page_index = 'statistique/statistique-index';



    public function index(Request $request) : InertiaResponse {
        //==============================================================================================================
        // Filtres de l'URL (?filtres[periode][du]=...&filtres[groupe]=12), normalisés comme ceux des listes
        //==============================================================================================================
        $definitions = self::get_definitions_filtres();
        $brut        = $request->input('filtres', []);
        $brut        = is_array($brut) ? $brut : [];

        $valeurs = [];

        foreach ($definitions as $definition) {
            $valeurs[$definition->get_nom()] = $definition->normaliser($brut[$definition->get_nom()] ?? null);
        }

        $defaut = StatistiqueService::get_filtres_par_defaut();

        $filtres = [
            ...$defaut,
            'date_debut'      => $valeurs['periode']['du'] ?? $defaut['date_debut'],
            'date_fin'        => $valeurs['periode']['au'] ?? $defaut['date_fin'],
            'cycle_id'        => $valeurs['cycle'],
            'niveau_etude_id' => $valeurs['niveau'],
            'groupe_id'       => $valeurs['groupe'],
            'module_id'       => $valeurs['module'],
            'enseignant_id'   => $valeurs['enseignant'],
        ];

        if ($filtres['date_fin'] < $filtres['date_debut']) {
            [$filtres['date_debut'], $filtres['date_fin']] = [$filtres['date_fin'], $filtres['date_debut']];
        }


        //==============================================================================================================
        // Comparaison avec la période précédente de même durée
        //==============================================================================================================
        $precedent = StatistiqueService::get_resume(StatistiqueService::get_filtres_periode_precedente($filtres));

        $annee = AnneeUniversitaireService::get_active();

        return Inertia::render(self::page_index, [
            'titre_page'    => "Statistiques",
            'breadcrumbs'   => [['title' => "Statistiques", 'href' => route('statistiques.index')]],
            'filtres'       => array_map(fn(TableFilter $definition) => $definition->get($valeurs[$definition->get_nom()]), $definitions),
            'periode'       => [
                'du'    => $filtres['date_debut'],
                'au'    => $filtres['date_fin'],
                'texte' => "Du " . Carbon::parse($filtres['date_debut'])
                                         ->format('d/m/Y') . " au " . Carbon::parse($filtres['date_fin'])
                                                                            ->format('d/m/Y'),
            ],
            'annee'         => $annee ? [
                'libelle' => $annee->libelle,
                'du'      => $annee->date_debut?->format('Y-m-d'),
                'au'      => $annee->date_fin?->format('Y-m-d'),
            ] : null,
            'resume'        => StatistiqueService::get_resume($filtres),
            'precedent'     => $precedent['nb_seances'] > 0 ? $precedent : null,
            'evolution'     => StatistiqueService::get_evolution_taux_par_semaine($filtres),
            'par_groupe'    => StatistiqueService::get_par_groupe($filtres),
            'par_module'    => StatistiqueService::get_par_module($filtres),
            'par_type'      => StatistiqueService::get_par_type($filtres),
            'creneaux'      => StatistiqueService::get_par_creneau($filtres),
            'top_etudiants' => StatistiqueService::get_top_etudiants($filtres),
        ]);
    }



    /**
     * @return TableFilter[]
     */
    protected static function get_definitions_filtres() : array {
        return [
            TableFilter::new('periode')
                       ->label("Période")
                       ->periode(),
            TableFilter::new('cycle')
                       ->label("Cycle")
                       ->select(CycleService::get_cycles_pour_select()),
            TableFilter::new('niveau')
                       ->label("Niveau d'études")
                       ->select(NiveauEtudeService::get_niveaux_pour_select()),
            TableFilter::new('groupe')
                       ->label("Groupe")
                       ->select(GroupeService::get_groupes_pour_select()),
            TableFilter
                ::new('module')
                ->label("Module")
                ->select(
                    Module::query()
                          ->orderBy('code')
                          ->get()
                          ->map(fn(Module $module) => ['valeur' => $module->id, 'label' => "{$module->code} — {$module->intitule}"])
                          ->all()
                ),
            TableFilter::new('enseignant')
                       ->label("Enseignant")
                       ->select(SeanceService::get_enseignants_pour_select()),
        ];
    }
}
