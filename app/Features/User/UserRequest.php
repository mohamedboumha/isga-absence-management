<?php

namespace App\Features\User;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UserRequest extends FormRequest {
    public function authorize() : bool {
        return true;
    }



    protected function get_cible() : ?User {
        $cle = $this->route('cle');

        return $cle ? User::query()
                          ->where('cle', $cle)
                          ->first() : null;
    }



    protected function prepareForValidation() : void {
        $cible = $this->get_cible();

        //==============================================================================================================
        // Compte enseignant : on ignore ce qui est envoyé, sauf "actif"
        //==============================================================================================================
        if ($cible && UserService::is_enseignant($cible)) {
            $this->merge([
                             'name'   => $cible->name,
                             'prenom' => $cible->prenom,
                             'email'  => $cible->email,
                             'role'   => $cible->role,
                         ]);
        }

        $this->merge([
                         'name'   => trim((string) $this->input('name')),
                         'prenom' => trim((string) $this->input('prenom')),
                         'email'  => strtolower(trim((string) $this->input('email'))),
                         'actif'  => $this->boolean('actif'),
                     ]);
    }



    public function rules() : array {
        $cible = $this->get_cible();

        $roles_autorises = $cible && UserService::is_enseignant($cible)
            ? [UserService::role_enseignant]
            : UserService::roles_administration;

        return [
            'name'   => ['required', 'max:100'],
            'prenom' => ['required', 'max:100'],
            'email'  => ['required', 'email', 'max:255', Rule::unique('users', 'email')
                                                             ->ignore($cible?->id)],
            'role'   => ['required', Rule::in($roles_autorises)],
            'actif'  => ['boolean'],
        ];
    }



    public function after() : array {
        return [
            function (Validator $validator) {
                if ($validator->errors()
                              ->isNotEmpty()) {
                    return;
                }

                $cible = $this->get_cible();

                /** @var User $moi */
                $moi = $this->user();

                //======================================================================================================
                // On ne se retire pas ses propres droits
                //======================================================================================================
                if ($cible && $cible->id === $moi->id) {
                    if (!$this->boolean('actif')) {
                        $validator->errors()
                                  ->add('actif', "Vous ne pouvez pas désactiver votre propre compte.");
                    }

                    if ($this->input('role') !== $moi->role) {
                        $validator->errors()
                                  ->add('role', "Vous ne pouvez pas modifier votre propre rôle.");
                    }
                }


                //======================================================================================================
                // Il doit toujours rester au moins un super-administrateur actif
                //======================================================================================================
                $reste_super_admin = $this->input('role') === UserService::role_super_admin && $this->boolean('actif');

                if ($cible && $cible->role === UserService::role_super_admin && $cible->actif && !$reste_super_admin
                    && UserService::nb_super_admins_actifs($cible->id) === 0) {
                    $validator->errors()
                              ->add('role', "Il doit rester au moins un super-administrateur actif.");
                }
            },
        ];
    }



    public function messages() : array {
        return [
            'name.required'   => "Le nom est obligatoire.",
            'prenom.required' => "Le prénom est obligatoire.",
            'email.required'  => "L'e-mail est obligatoire.",
            'email.email'     => "L'e-mail n'est pas valide.",
            'email.unique'    => "Cet e-mail est déjà utilisé par un autre compte.",
            'role.required'   => "Le rôle est obligatoire.",
            'role.in'         => "Ce rôle n'est pas autorisé pour ce compte.",
        ];
    }
}
