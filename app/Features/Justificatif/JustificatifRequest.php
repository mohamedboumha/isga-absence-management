<?php

namespace App\Features\Justificatif;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class JustificatifRequest extends FormRequest {
    public function authorize() : bool {
        return true;
    }



    protected function prepareForValidation() : void {
        $this->merge([
                         'motif' => trim((string) $this->input('motif')) ?: null,
                     ]);
    }



    public function rules() : array {
        //==============================================================================================================
        // Le document est obligatoire à la création, facultatif en modification (on garde l'ancien)
        //==============================================================================================================
        $cle = $this->route('cle');

        return [
            'etudiant_id' => ['required', Rule::exists('etudiants', 'id')
                                              ->whereNull('deleted_at')],
            'type'        => ['required', Rule::in(array_keys(JustificatifService::types))],
            'date_debut'  => ['required', 'date'],
            'date_fin'    => ['required', 'date', 'after_or_equal:date_debut'],
            'motif'       => ['nullable', 'max:2000'],
            'fichier'     => [
                $cle ? 'nullable' : 'required',
                'file',
                'mimes:' . implode(',', JustificatifService::extensions),
                'max:' . JustificatifService::taille_max_ko,
            ],
        ];
    }



    public function messages() : array {
        return [
            'etudiant_id.required'    => "L'étudiant est obligatoire.",
            'type.required'           => "Le type est obligatoire.",
            'date_debut.required'     => "La date de début est obligatoire.",
            'date_fin.required'       => "La date de fin est obligatoire.",
            'date_fin.after_or_equal' => "La date de fin doit être égale ou postérieure à la date de début.",
            'motif.max'               => "Le motif ne doit pas dépasser 2000 caractères.",
            'fichier.required'        => "Le document justificatif est obligatoire.",
            'fichier.mimes'           => "Le document doit être un PDF ou une image (JPG, PNG).",
            'fichier.max'             => "Le document ne doit pas dépasser 5 Mo.",
        ];
    }
}
