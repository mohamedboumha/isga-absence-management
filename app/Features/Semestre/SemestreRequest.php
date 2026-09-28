<?php

namespace App\Features\Semestre;

use App\Features\AnneeUniversitaire\AnneeUniversitaire;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SemestreRequest extends FormRequest {
    public function authorize() : bool {
        return true;
    }



    protected function prepareForValidation() : void {
        //==============================================================================================================
        // " s1 " => "S1"
        //==============================================================================================================
        $this->merge([
                         'libelle' => strtoupper(trim((string) $this->input('libelle'))),
                     ]);
    }



    public function rules() : array {
        $cle = $this->route('cle');

        return [
            'annee_universitaire_id' => [
                'required',
                Rule::exists('annees_universitaires', 'id')
                    ->whereNull('deleted_at'),
            ],
            'libelle'                => [
                'required',
                'regex:/^S([1-9]|10)$/',
                Rule::unique('semestres', 'libelle')
                    ->where('annee_universitaire_id', $this->input('annee_universitaire_id'))
                    ->ignore($cle, 'cle'),
            ],
            'date_debut'             => ['required', 'date'],
            'date_fin'               => ['required', 'date', 'after:date_debut'],
        ];
    }



    public function after() : array {
        return [
            function (Validator $validator) {
                //======================================================================================================
                // Les dates du semestre doivent être comprises dans celles de l'année
                //======================================================================================================
                if ($validator->errors()
                              ->isNotEmpty()) {
                    return;
                }

                $annee = AnneeUniversitaire::find($this->input('annee_universitaire_id'));

                if ($this->input('date_debut') < $annee->date_debut->format('Y-m-d')) {
                    $validator->errors()
                              ->add('date_debut', "Le semestre ne peut pas commencer avant l'année ({$annee->date_debut->format('d/m/Y')}).");
                }

                if ($this->input('date_fin') > $annee->date_fin->format('Y-m-d')) {
                    $validator->errors()
                              ->add('date_fin', "Le semestre ne peut pas finir après l'année ({$annee->date_fin->format('d/m/Y')}).");
                }
            },
        ];
    }



    public function messages() : array {
        return [
            'annee_universitaire_id.required' => "L'année universitaire est obligatoire.",
            'annee_universitaire_id.exists'   => "Cette année universitaire n'existe pas.",
            'libelle.required'                => "Le libellé est obligatoire.",
            'libelle.regex'                   => "Le libellé doit être S1 à S10.",
            'libelle.unique'                  => "Ce semestre existe déjà pour cette année.",
            'date_debut.required'             => "La date de début est obligatoire.",
            'date_fin.required'               => "La date de fin est obligatoire.",
            'date_fin.after'                  => "La date de fin doit être après la date de début.",
        ];
    }
}
