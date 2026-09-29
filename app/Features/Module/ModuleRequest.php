<?php

namespace App\Features\Module;

use App\Features\Filiere\FiliereService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'filiere_id'     => [
                'required',
                Rule::exists('filieres', 'id')
                    ->whereNull('deleted_at'),
            ],
            'code'           => [
                'required',
                'regex:/^[A-Z0-9-]{2,15}$/',
                Rule::unique('modules', 'code')
                    ->ignore($cle, 'cle'),
            ],
            'intitule'       => ['required', 'max:150'],
            'semestre'       => ['required', Rule::in(FiliereService::semestres)],
            'volume_horaire' => ['required', 'integer', 'min:1', 'max:300'],
        ];
    }



    public function messages() : array {
        return [
            'filiere_id.required'     => "La filière est obligatoire.",
            'filiere_id.exists'       => "Cette filière n'existe pas.",
            'code.required'           => "Le code est obligatoire.",
            'code.regex'              => "Le code doit contenir 2 à 15 lettres, chiffres ou tirets (ex. GI-BDD).",
            'code.unique'             => "Ce code est déjà utilisé par un autre module.",
            'intitule.required'       => "L'intitulé est obligatoire.",
            'intitule.max'            => "L'intitulé ne doit pas dépasser 150 caractères.",
            'semestre.required'       => "Le semestre est obligatoire.",
            'semestre.in'             => "Le semestre doit être entre S1 et S10.",
            'volume_horaire.required' => "Le volume horaire est obligatoire.",
            'volume_horaire.integer'  => "Le volume horaire doit être un nombre entier d'heures.",
            'volume_horaire.min'      => "Le volume horaire doit être d'au moins 1 heure.",
            'volume_horaire.max'      => "Le volume horaire ne doit pas dépasser 300 heures.",
        ];
    }
}
