<?php

namespace App\Features\Filiere;

use App\_Core\Services\CouleurService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FiliereRequest extends FormRequest {
    public function authorize() : bool {
        return true;
    }



    protected function prepareForValidation() : void {
        //==============================================================================================================
        // " gi " => "GI" ; description vide => null ; couleur vide => null (couleur du cycle)
        //==============================================================================================================
        $this->merge([
                         'code'        => strtoupper(trim((string) $this->input('code'))),
                         'nom'         => trim((string) $this->input('nom')),
                         'description' => trim((string) $this->input('description')) ?: null,
                         'couleur'     => $this->input('couleur') ? strtoupper((string) $this->input('couleur')) : null,
                     ]);
    }



    public function rules() : array {
        $cle = $this->route('cle');

        return [
            'cycle_id'    => ['required', Rule::exists('cycles', 'id')
                                              ->whereNull('deleted_at')],
            'code'        => [
                'required',
                'regex:/^[A-Z0-9-]{2,10}$/',
                Rule::unique('filieres', 'code')
                    ->ignore($cle, 'cle'),
            ],
            'nom'         => [
                'required',
                'max:150',
                Rule::unique('filieres', 'nom')
                    ->ignore($cle, 'cle'),
            ],
            'couleur'     => ['nullable', Rule::in(CouleurService::get_valeurs())],
            'description' => ['nullable', 'max:2000'],
        ];
    }



    public function messages() : array {
        return [
            'cycle_id.required' => "Le cycle est obligatoire.",
            'code.required'     => "Le code est obligatoire.",
            'code.regex'        => "Le code doit contenir 2 à 10 lettres, chiffres ou tirets (ex. GI).",
            'code.unique'       => "Ce code est déjà utilisé par une autre filière.",
            'nom.required'      => "Le nom est obligatoire.",
            'nom.max'           => "Le nom ne doit pas dépasser 150 caractères.",
            'nom.unique'        => "Une filière porte déjà ce nom.",
            'couleur.in'        => "Choisissez une couleur de la palette.",
            'description.max'   => "La description ne doit pas dépasser 2000 caractères.",
        ];
    }
}
