<?php

namespace App\Features\Groupe;

use App\Features\Filiere\FiliereService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GroupeRequest extends FormRequest {
    public function authorize() : bool {
        return true;
    }



    protected function prepareForValidation() : void {
        //==============================================================================================================
        // " gi-l3-a " => "GI-L3-A"
        //==============================================================================================================
        $this->merge([
                         'nom' => strtoupper(trim((string) $this->input('nom'))),
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
            'filiere_id'             => [
                'required',
                Rule::exists('filieres', 'id')
                    ->whereNull('deleted_at'),
            ],
            'niveau'                 => [
                'required',
                Rule::in(FiliereService::niveaux),
            ],
            'nom'                    => [
                'required',
                'max:30',
                'regex:/^[A-Z0-9 -]+$/',
                Rule::unique('groupes', 'nom')
                    ->where('annee_universitaire_id', $this->input('annee_universitaire_id'))
                    ->ignore($cle, 'cle'),
            ],
        ];
    }



    public function messages() : array {
        return [
            'annee_universitaire_id.required' => "L'année universitaire est obligatoire.",
            'annee_universitaire_id.exists'   => "Cette année universitaire n'existe pas.",
            'filiere_id.required'             => "La filière est obligatoire.",
            'filiere_id.exists'               => "Cette filière n'existe pas.",
            'niveau.required'                 => "Le niveau est obligatoire.",
            'niveau.in'                       => "Le niveau doit être L1, L2, L3, M1 ou M2.",
            'nom.required'                    => "Le nom est obligatoire.",
            'nom.max'                         => "Le nom ne doit pas dépasser 30 caractères.",
            'nom.regex'                       => "Le nom ne peut contenir que des lettres, chiffres, espaces et tirets.",
            'nom.unique'                      => "Ce nom de groupe existe déjà pour cette année.",
        ];
    }
}
