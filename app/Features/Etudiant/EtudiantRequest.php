<?php

namespace App\Features\Etudiant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EtudiantRequest extends FormRequest {
    public function authorize() : bool {
        return true;
    }



    protected function prepareForValidation() : void {
        $this->merge([
                         'cne'            => strtoupper(trim((string) $this->input('cne'))),
                         'nom'            => trim((string) $this->input('nom')),
                         'prenom'         => trim((string) $this->input('prenom')),
                         'email'          => strtolower(trim((string) $this->input('email'))) ?: null,
                         'telephone'      => preg_replace('/\s+/', '', (string) $this->input('telephone')) ?: null,
                         'date_naissance' => $this->input('date_naissance') ?: null,
                     ]);
    }



    public function rules() : array {
        $cle = $this->route('cle');

        return [
            'groupe_id'      => [
                'required',
                Rule::exists('groupes', 'id')
                    ->whereNull('deleted_at'),
            ],
            'cne'            => [
                'required',
                'regex:/^[A-Z][0-9]{9}$/',
                Rule::unique('etudiants', 'cne')
                    ->ignore($cle, 'cle'),
            ],
            'nom'            => ['required', 'max:100'],
            'prenom'         => ['required', 'max:100'],
            'email'          => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('etudiants', 'email')
                    ->ignore($cle, 'cle'),
            ],
            'telephone'      => ['nullable', 'regex:/^\+?[0-9]{8,15}$/'],
            'date_naissance' => ['nullable', 'date', 'after:1950-01-01', 'before:today'],
        ];
    }



    public function messages() : array {
        return [
            'groupe_id.required'    => "Le groupe est obligatoire.",
            'groupe_id.exists'      => "Ce groupe n'existe pas.",
            'cne.required'          => "Le CNE est obligatoire.",
            'cne.regex'             => "Le CNE doit être une lettre suivie de 9 chiffres (ex. R130245678).",
            'cne.unique'            => "Ce CNE est déjà attribué à un autre étudiant.",
            'nom.required'          => "Le nom est obligatoire.",
            'prenom.required'       => "Le prénom est obligatoire.",
            'email.email'           => "L'e-mail n'est pas valide.",
            'email.unique'          => "Cet e-mail est déjà utilisé par un autre étudiant.",
            'telephone.regex'       => "Le téléphone doit contenir 8 à 15 chiffres (ex. 0612345678).",
            'date_naissance.after'  => "La date de naissance n'est pas plausible.",
            'date_naissance.before' => "La date de naissance doit être dans le passé.",
        ];
    }
}
