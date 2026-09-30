<?php

namespace App\_Core\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TableauExport implements FromArray, WithHeadings, ShouldAutoSize, WithTitle, WithStyles {
    public function __construct(
        protected array  $entetes,
        protected array  $lignes,
        protected string $titre = 'Export',
    ) {
    }



    public function array() : array {
        return $this->lignes;
    }



    public function headings() : array {
        return $this->entetes;
    }



    public function title() : string {
        //==============================================================================================================
        // Excel limite le nom d'une feuille à 31 caractères
        //==============================================================================================================
        return mb_substr($this->titre, 0, 31);
    }



    public function styles(Worksheet $sheet) : array {
        return [
            1 => ['font' => ['bold' => true]], // ligne d'en-têtes en gras
        ];
    }
}
