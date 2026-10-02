<?php

namespace App\Features\Etudiant;

use App\Features\AnneeUniversitaire\AnneeUniversitaireService;
use App\Features\Groupe\Groupe;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

//======================================================================================================================
// Fichier au format d'import des étudiants :
// - sans étudiants : le modèle à remplir (une ligne d'exemple)
// - avec des étudiants : l'export de la liste, réimportable tel quel après correction
//======================================================================================================================
class EtudiantImportModele implements Export, WithMultipleSheets {
    const array colonnes = ['CNE', 'Nom', 'Prénom', 'E-mail', 'Téléphone', 'Date de naissance', 'Groupe'];



    public function __construct(private readonly ?Collection $etudiants = null) {
    }



    //==================================================================================================================
    // Deux feuilles : les étudiants, et les groupes de l'année active (pour copier les noms exacts)
    //==================================================================================================================
    public function sheets() : array {
        $annee   = AnneeUniversitaireService::get_active();
        $groupes = $annee
            ? Groupe::query()
                    ->where('annee_universitaire_id', $annee->id)
                    ->with('niveau_etude')
                    ->orderBy('nom')
                    ->get()
            : collect();

        $lignes = $this->etudiants
            ? $this->etudiants->map(fn(Etudiant $etudiant) => [
                $etudiant->cne,
                $etudiant->nom,
                $etudiant->prenom,
                $etudiant->email,
                $etudiant->telephone,
                $etudiant->date_naissance?->format('d/m/Y'),
                $etudiant->inscription_active?->groupe?->nom,
            ])
                              ->all()
            : [[
                   'R130000001',
                   'EL ALAMI',
                   'Yasmine',
                   'yasmine.elalami@exemple.ma',
                   '0612345678',
                   '15/03/2007',
                   $groupes->first()?->nom ?? '1AP-A',
               ]];

        return [
            new class($lignes) implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithColumnFormatting {
                public function __construct(private readonly array $lignes) {
                }



                public function array() : array {
                    return $this->lignes;
                }



                public function headings() : array {
                    return EtudiantImportModele::colonnes;
                }



                public function title() : string {
                    return "Étudiants";
                }

                //======================================================================================================
                // CNE, téléphone et date en texte : Excel ne les transforme pas (0 du début, format de date)
                //======================================================================================================
                public function columnFormats() : array {
                    return [
                        'A' => NumberFormat::FORMAT_TEXT,
                        'E' => NumberFormat::FORMAT_TEXT,
                        'F' => NumberFormat::FORMAT_TEXT,
                    ];
                }
            },

            new class($groupes->map(fn(Groupe $groupe) => [$groupe->nom, $groupe->niveau_etude->libelle])
                              ->all(), $annee?->libelle) implements FromArray, WithHeadings, WithTitle, ShouldAutoSize {
                public function __construct(private readonly array $lignes, private readonly ?string $annee) {
                }



                public function array() : array {
                    return $this->lignes;
                }



                public function headings() : array {
                    return ["Groupe" . ($this->annee ? " ({$this->annee})" : ""), "Niveau d'études"];
                }



                public function title() : string {
                    return "Groupes";
                }
            },
        ];
    }
}
