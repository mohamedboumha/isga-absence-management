<?php

namespace App\Features\Statistique;

use App\_Core\Exports\TableauExport;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class StatistiquesExport implements WithMultipleSheets {
    public function __construct(protected array $filtres) {
    }



    public function sheets() : array {
        $resume = StatistiqueService::get_resume($this->filtres);

        return [
            new TableauExport(
                ['Indicateur', 'Valeur'],
                [
                    ['Période', "Du {$this->filtres['date_debut']} au {$this->filtres['date_fin']}"],
                    ["Taux d'absence (%)", $resume['taux']],
                    ['Séances tenues', $resume['nb_seances']],
                    ['Absences', $resume['nb_absences']],
                    ['Justifiées', $resume['nb_justifiees']],
                    ['Non justifiées', $resume['nb_non_justifiees']],
                    ["Heures d'absence", $resume['heures_absence']],
                ],
                'Résumé'
            ),
            new TableauExport(
                ['Groupe', 'Absences', "Heures d'absence", 'Taux (%)'],
                array_map(fn(array $ligne) => [$ligne['nom'], $ligne['nb_absences'], $ligne['heures_absence'], $ligne['taux']], StatistiqueService::get_par_groupe($this->filtres)),
                'Par groupe'
            ),
            new TableauExport(
                ['Module', 'Séances', 'Absences', "Heures d'absence", 'Taux (%)'],
                array_map(fn(array $ligne) => [$ligne['module'], $ligne['nb_seances'], $ligne['nb_absences'], $ligne['heures_absence'], $ligne['taux']], StatistiqueService::get_par_module($this->filtres)),
                'Par module'
            ),
            new TableauExport(
                ['CNE', 'Étudiant', 'Groupe', 'Absences', 'Non justifiées', "Heures d'absence", 'Taux (%)'],
                array_map(
                    fn(array $ligne) => [$ligne['cne'], $ligne['nom_complet'], $ligne['groupe'], $ligne['nb_absences'], $ligne['nb_non_justifiees'], $ligne['heures_absence'], $ligne['taux']],
                    StatistiqueService::get_top_etudiants($this->filtres, 100000)
                ),
                'Étudiants'
            ),
        ];
    }
}
