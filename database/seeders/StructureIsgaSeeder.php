<?php

namespace Database\Seeders;

use App\Features\Cycle\Cycle;
use App\Features\Filiere\Filiere;
use App\Features\Journal\JournalService;
use App\Features\NiveauEtude\NiveauEtude;
use Illuminate\Database\Seeder;

class StructureIsgaSeeder extends Seeder {
    //==================================================================================================================
    // Cycles : code => [nom, durée, couleur]
    //==================================================================================================================
    const array cycles = [
        'ING' => ["Cycle ingénieur", 5, '#0369A1'],
        'MST' => ["Master", 2, '#7C3AED'],
        'LIC' => ["Licence", 1, '#B45309'],
    ];

    //==================================================================================================================
    // Filières (spécialités) par cycle : code => couleur. Le nom complet est à compléter depuis l'écran Filières.
    //==================================================================================================================
    const array filieres = [
        'ING' => [
            'ISI'   => '#0E7490',
            'ISII'  => '#4F46E5',
            'IDWM'  => '#15803D',
            'SIICQ' => '#4D7C0F',
            'IABD'  => '#BE185D',
            'IRSS'  => '#475569',
        ],
        'MST' => [
            'CF'   => '#86198F',
            'MDEC' => '#C2410C',
            'CCA'  => '#1E3A8A',
            'IF'   => '#0F766E',
        ],
    ];

    //==================================================================================================================
    // Niveaux : code => [cycle, année dans le cycle, filière (null = tronc commun), libellé]
    //==================================================================================================================
    const array niveaux = [
        '1AP'       => ['ING', 1, null, "1ère année préparatoire"],
        '2AP'       => ['ING', 2, null, "2ème année préparatoire"],
        'ISI-1'     => ['ING', 3, 'ISI', "1ère année cycle ingénieur — ISI"],
        'ISII-1'    => ['ING', 3, 'ISII', "1ère année cycle ingénieur — ISII"],
        'ISI-2'     => ['ING', 4, 'ISI', "2ème année cycle ingénieur — ISI"],
        'ISII-2'    => ['ING', 4, 'ISII', "2ème année cycle ingénieur — ISII"],
        '3CI-IDWM'  => ['ING', 5, 'IDWM', "3ème année cycle ingénieur — IDWM"],
        '3CI-SIICQ' => ['ING', 5, 'SIICQ', "3ème année cycle ingénieur — SIICQ"],
        '3CI-IABD'  => ['ING', 5, 'IABD', "3ème année cycle ingénieur — IABD"],
        '3CI-IRSS'  => ['ING', 5, 'IRSS', "3ème année cycle ingénieur — IRSS"],
        'M1-CF'     => ['MST', 1, 'CF', "1ère année Master — CF"],
        'M1-MDEC'   => ['MST', 1, 'MDEC', "1ère année Master — MDEC"],
        'M2-CCA'    => ['MST', 2, 'CCA', "2ème année Master — CCA"],
        'M2-IF'     => ['MST', 2, 'IF', "2ème année Master — IF"],
        'M2-MDEC'   => ['MST', 2, 'MDEC', "2ème année Master — MDEC"],
        'LIC'       => ['LIC', 1, null, "Licence en Management des Entreprises et Systèmes Digitaux"],
    ];

    //==================================================================================================================
    // Parcours : niveau => niveaux possibles l'année suivante
    //==================================================================================================================
    const array parcours = [
        '1AP'     => ['2AP'],
        '2AP'     => ['ISI-1', 'ISII-1'],
        'ISI-1'   => ['ISI-2'],
        'ISII-1'  => ['ISII-2'],
        'ISI-2'   => ['3CI-IDWM', '3CI-SIICQ', '3CI-IABD', '3CI-IRSS'],
        'ISII-2'  => ['3CI-IDWM', '3CI-SIICQ', '3CI-IABD', '3CI-IRSS'],
        'M1-CF'   => ['M2-CCA', 'M2-IF'],
        'M1-MDEC' => ['M2-MDEC'],
    ];



    public function run() : void {
        JournalService::$actif = false;

        $cycles = collect(self::cycles)->map(fn(array $cycle, string $code) => Cycle::create([
                                                                                                 'code'      => $code,
                                                                                                 'nom'       => $cycle[0],
                                                                                                 'nb_annees' => $cycle[1],
                                                                                                 'couleur'   => $cycle[2],
                                                                                             ]));

        $filieres = collect();

        foreach (self::filieres as $code_cycle => $codes) {
            foreach ($codes as $code => $couleur) {
                $filieres[$code] = Filiere::create([
                                                       'cycle_id' => $cycles[$code_cycle]->id,
                                                       'code'     => $code,
                                                       'nom'      => $code,
                                                       'couleur'  => $couleur,
                                                   ]);
            }
        }

        $niveaux = collect(self::niveaux)->map(fn(array $niveau, string $code) => NiveauEtude::create([
                                                                                                          'cycle_id'     => $cycles[$niveau[0]]->id,
                                                                                                          'annee_cycle'  => $niveau[1],
                                                                                                          'filiere_id'   => $niveau[2] ? $filieres[$niveau[2]]->id : null,
                                                                                                          'code'         => $code,
                                                                                                          'libelle'      => $niveau[3],
                                                                                                          'nb_semestres' => 2,
                                                                                                      ]));

        foreach (self::parcours as $code => $suivants) {
            $niveaux[$code]->suivants()
                           ->sync(collect($suivants)
                                      ->map(fn(string $suivant) => $niveaux[$suivant]->id)
                                      ->all());
        }

        JournalService::$actif = true;
    }
}
