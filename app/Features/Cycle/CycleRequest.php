<?php

namespace App\Features\Cycle;

use App\_Core\Services\CouleurService;
use App\Features\NiveauEtude\NiveauEtude;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CycleRequest extends FormRequest {
    public function authorize() : bool {
        return true;
    }



    protected function prepareForValidation() : void {
        $this->merge([
                         'code'    => strtoupper(trim((string) $this->input('code'))),
                         'nom'     => trim((string) $this->input('nom')),
                         'couleur' => strtoupper((string) $this->input('couleur')),
                     ]);
    }



    public function rules() : array {
        $cle = $this->route('cle');

        return [
            'code'      => ['required', 'regex:/^[A-Z0-9-]{2,10}$/', Rule::unique('cycles', 'code')
                                                                         ->ignore($cle, 'cle')],
            'nom'       => ['required', 'max:100', Rule::unique('cycles', 'nom')
                                                       ->ignore($cle, 'cle')],
            'nb_annees' => ['required', 'integer', 'min:1', 'max:8'],
            'couleur'   => ['required', Rule::in(CouleurService::get_valeurs())],
        ];
    }



    public function after() : array {
        return [
            function (Validator $validator) {
                //======================================================================================================
                // On ne raccourcit pas un cycle en dessous de ses niveaux existants
                //======================================================================================================
                $cycle = Cycle::get_by_cle($this->route('cle'));

                if (!$cycle || $validator->errors()
                                         ->isNotEmpty()) {
                    return;
                }

                $annee_max = (int) NiveauEtude::query()
                                              ->where('cycle_id', $cycle->id)
                                              ->max('annee_cycle');

                if ((int) $this->input('nb_annees') < $annee_max) {
                    $validator->errors()
                              ->add('nb_annees', "Ce cycle a déjà des niveaux jusqu'à la {$annee_max}e année.");
                }
            },
        ];
    }



    public function messages() : array {
        return [
            'code.required'      => "Le code est obligatoire.",
            'code.regex'         => "Le code doit contenir 2 à 10 lettres, chiffres ou tirets (ex. ING).",
            'code.unique'        => "Ce code est déjà utilisé.",
            'nom.required'       => "Le nom est obligatoire.",
            'nom.unique'         => "Un cycle porte déjà ce nom.",
            'nb_annees.required' => "La durée est obligatoire.",
            'nb_annees.min'      => "Un cycle dure au moins 1 an.",
            'nb_annees.max'      => "Un cycle ne peut pas dépasser 8 ans.",
            'couleur.required'   => "La couleur est obligatoire.",
            'couleur.in'         => "Choisissez une couleur de la palette.",
        ];
    }
}
