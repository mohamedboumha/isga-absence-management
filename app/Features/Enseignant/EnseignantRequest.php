<?php

namespace App\Features\Enseignant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EnseignantRequest extends FormRequest {
    public function authorize() : bool {
        return true;
    }



    protected function prepareForValidation() : void {
        $this->merge([
                         'nom'       => trim((string) $this->input('nom')),
                         'prenom'    => trim((string) $this->input('prenom')),
                         'email'     => strtolower(trim((string) $this->input('email'))),
                         'telephone' => preg_replace('/\s+/', '', (string) $this->input('telephone')) ?: null,
                         'modules'   => $this->input('modules', []),
                     ]);
    }



    public function rules() : array {
        //==============================================================================================================
        // Fiche et compte en cours de modification (null en création)
        //==============================================================================================================
        $cle        = $this->route('cle');
        $enseignant = $cle ? Enseignant::get_by_cle($cle) : null;

        return [
            'nom'       => ['required', 'max:100'],
            'prenom'    => ['required', 'max:100'],
            'email'     => [
                'required',
                'email',
                'max:255',
                Rule::unique('enseignants', 'email')
                    ->ignore($cle, 'cle'),
                Rule::unique('users', 'email')
                    ->ignore($enseignant?->user_id),
            ],
            'telephone' => ['nullable', 'regex:/^\+?[0-9]{8,15}$/'],
            'modules'   => ['array'],
            'modules.*' => [Rule::exists('modules', 'id')
                                ->whereNull('deleted_at')],
        ];
    }



    public function messages() : array {
        return [
            'nom.required'     => "Le nom est obligatoire.",
            'prenom.required'  => "Le prénom est obligatoire.",
            'email.required'   => "L'e-mail est obligatoire.",
            'email.email'      => "L'e-mail n'est pas valide.",
            'email.unique'     => "Cet e-mail est déjà utilisé.",
            'telephone.regex'  => "Le téléphone doit contenir 8 à 15 chiffres (ex. 0612345678).",
            'modules.*.exists' => "Un des modules choisis n'existe pas.",
        ];
    }
}
