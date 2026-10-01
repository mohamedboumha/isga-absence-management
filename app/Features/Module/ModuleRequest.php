<?php

namespace App\Features\Module;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Features\NiveauEtude\NiveauEtude;
use Illuminate\Validation\Validator;

class ModuleRequest extends FormRequest {
    public function authorize() : bool {
        return true;
    }



    protected function prepareForValidation() : void {
        //==============================================================================================================
        // " gi-bdd " => "GI-BDD"
        //==============================================================================================================
        $this->merge([
                         'code'     => strtoupper(trim((string) $this->input('code'))),
                         'intitule' => trim((string) $this->input('intitule')),
                     ]);
    }



    public function rules() : array {
        $cle = $this->route('cle');

        return [
            'code'            => [
                'required',
                'regex:/^[A-Z0-9-]{2,15}$/',
                Rule::unique('modules', 'code')
                    ->ignore($cle, 'cle'),
            ],
            'intitule'        => ['required', 'max:150'],
            'volume_horaire'  => ['required', 'integer', 'min:1', 'max:300'],
            'niveau_etude_id' => [
                'required',
                Rule::exists('niveaux_etudes', 'id')
                    ->whereNull('deleted_at'),
            ],
            'semestre'        => ['required', 'integer', 'min:1'],
        ];
    }



    public function after() : array {
        return [
            function (Validator $validator) {
                if ($validator->errors()
                              ->isNotEmpty()) {
                    return;
                }

                $niveau = NiveauEtude::findOrFail((int) $this->input('niveau_etude_id'));

                if ((int) $this->input('semestre') > $niveau->nb_semestres) {
                    $validator->errors()
                              ->add('semestre', "Le niveau {$niveau->code} n'a que {$niveau->nb_semestres} semestre(s).");
                }
            },
        ];
    }



    public function messages() : array {
        return [
            'niveau_etude_id.required' => "Le niveau d'études est obligatoire.",
            'niveau_etude_id.exists'   => "Ce niveau d'études n'existe pas.",
            'semestre.required'        => "Le semestre est obligatoire.",
            'semestre.min'             => "Le semestre commence à 1.",
            'code.required'            => "Le code est obligatoire.",
            'code.regex'               => "Le code doit contenir 2 à 15 lettres, chiffres ou tirets (ex. GI-BDD).",
            'code.unique'              => "Ce code est déjà utilisé par un autre module.",
            'intitule.required'        => "L'intitulé est obligatoire.",
            'intitule.max'             => "L'intitulé ne doit pas dépasser 150 caractères.",
            'volume_horaire.required'  => "Le volume horaire est obligatoire.",
            'volume_horaire.integer'   => "Le volume horaire doit être un nombre entier d'heures.",
            'volume_horaire.min'       => "Le volume horaire doit être d'au moins 1 heure.",
            'volume_horaire.max'       => "Le volume horaire ne doit pas dépasser 300 heures.",
        ];
    }
}
