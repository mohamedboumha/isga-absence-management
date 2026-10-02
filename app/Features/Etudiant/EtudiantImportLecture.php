<?php

namespace App\Features\Etudiant;

use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

//======================================================================================================================
// Lecture du fichier : la première ligne donne les en-têtes ("Prénom" => "prenom", "E-mail" => "e_mail"...)
// Les lignes sont lues par Excel::toArray() ; l'analyse est faite par EtudiantImportService
//======================================================================================================================
class EtudiantImportLecture implements ToArray, WithHeadingRow, WithCalculatedFormulas {
    public function array(array $array) : void {
        //
    }
}
