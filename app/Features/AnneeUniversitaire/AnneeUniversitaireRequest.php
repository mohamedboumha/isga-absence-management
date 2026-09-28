<?php

namespace App\Features\AnneeUniversitaire;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AnneeUniversitaireRequest extends FormRequest {
    public function authorize() : bool {
        return true; // la route est déjà protégée par le middleware auth
    }



    protected function prepareForValidation() : void {
        //==============================================================================================================
        // La case à cocher envoie "on", "1", true ou rien : on la convertit en vrai booléen
        //==============================================================================================================
        $this->merge([
                         'active' => $this->boolean('active'),
                     ]);
    }



    public function rules() : array {
        //==============================================================================================================
        // Clé de l'année en cours de modification (null en création)
        //==============================================================================================================
        $cle = $this->route('cle');

        return [
            'libelle'    => [
                'required',
                'regex:/^\d{4}-\d{4}$/',
                Rule::unique('annees_universitaires', 'libelle')->ignore($cle, 'cle'),
            ],
            'date_debut' => ['required', 'date'],
            'date_fin'   => ['required', 'date', 'after:date_debut'],
            'active'     => ['boolean'],
        ];
    }



    public function after() : array {
        return [
            function (Validator $validator) {
                //======================================================================================================
                // Le libellé doit suivre les dates : "2026-2027" => début en 2026, fin en 2027
                //======================================================================================================
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                [$annee_1, $annee_2] = array_map('intval', explode('-', $this->input('libelle')));

                if ($annee_2 !== $annee_1 + 1) {
                    $validator->errors()->add('libelle', "Les deux années doivent se suivre (ex. 2026-2027).");
                }

                if ((int) date('Y', strtotime($this->input('date_debut'))) !== $annee_1) {
                    $validator->errors()->add('date_debut', "La date de début doit être en $annee_1.");
                }

                if ((int) date('Y', strtotime($this->input('date_fin'))) !== $annee_2) {
                    $validator->errors()->add('date_fin', "La date de fin doit être en $annee_2.");
                }
            },
        ];
    }



    public function messages() : array {
        return [
            'libelle.required'    => "Le libellé est obligatoire.",
            'libelle.regex'       => "Le libellé doit avoir le format AAAA-AAAA (ex. 2026-2027).",
            'libelle.unique'      => "Cette année universitaire existe déjà.",
            'date_debut.required' => "La date de début est obligatoire.",
            'date_fin.required'   => "La date de fin est obligatoire.",
            'date_fin.after'      => "La date de fin doit être après la date de début.",
        ];
    }
}
