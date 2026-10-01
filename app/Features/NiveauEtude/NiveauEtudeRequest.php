<?php

namespace App\Features\NiveauEtude;

use App\Features\Cycle\Cycle;
use App\Features\Filiere\Filiere;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class NiveauEtudeRequest extends FormRequest {
    public function authorize() : bool {
        return true;
    }



    protected function prepareForValidation() : void {
        $this->merge([
                         'code'     => strtoupper(trim((string) $this->input('code'))),
                         'libelle'  => trim((string) $this->input('libelle')),
                         'suivants' => $this->input('suivants', []),
                     ]);
    }



    public function rules() : array {
        $cle = $this->route('cle');

        return [
            'cycle_id'     => ['required', Rule::exists('cycles', 'id')
                                               ->whereNull('deleted_at')],
            'filiere_id'   => ['nullable', Rule::exists('filieres', 'id')
                                               ->whereNull('deleted_at')],
            'annee_cycle'  => ['required', 'integer', 'min:1'],
            'code'         => ['required', 'regex:/^[A-Z0-9-]{2,20}$/', Rule::unique('niveaux_etudes', 'code')
                                                                            ->ignore($cle, 'cle')],
            'libelle'      => ['required', 'max:150'],
            'nb_semestres' => ['required', 'integer', 'min:1', 'max:4'],
            'suivants'     => ['array'],
            'suivants.*'   => ['integer', 'distinct', Rule::exists('niveaux_etudes', 'id')
                                                          ->whereNull('deleted_at')],
        ];
    }



    public function after() : array {
        return [
            function (Validator $validator) {
                if ($validator->errors()
                              ->isNotEmpty()) {
                    return;
                }

                $cycle       = Cycle::findOrFail((int) $this->input('cycle_id'));
                $annee_cycle = (int) $this->input('annee_cycle');

                //======================================================================================================
                // L'année doit exister dans le cycle
                //======================================================================================================
                if ($annee_cycle > $cycle->nb_annees) {
                    $validator->errors()
                              ->add('annee_cycle', "Le cycle {$cycle->nom} ne dure que {$cycle->nb_annees} an(s).");

                    return;
                }


                //======================================================================================================
                // La filière appartient au même cycle
                //======================================================================================================
                $filiere = $this->input('filiere_id') ? Filiere::find((int) $this->input('filiere_id')) : null;

                if ($filiere && $filiere->cycle_id !== $cycle->id) {
                    $validator->errors()
                              ->add('filiere_id', "La filière {$filiere->code} n'appartient pas au cycle {$cycle->nom}.");
                }


                //======================================================================================================
                // Parcours : même cycle, exactement l'année suivante ; rien après la dernière année
                //======================================================================================================
                $suivants = NiveauEtude::query()
                                       ->whereKey($this->input('suivants', []))
                                       ->get();

                if ($suivants->isNotEmpty() && $annee_cycle >= $cycle->nb_annees) {
                    $validator->errors()
                              ->add('suivants', "Dernière année du cycle : les étudiants admis sont diplômés, il n'y a pas de niveau suivant.");

                    return;
                }

                foreach ($suivants as $suivant) {
                    if ($suivant->cycle_id !== $cycle->id || $suivant->annee_cycle !== $annee_cycle + 1) {
                        $validator->errors()
                                  ->add('suivants', "{$suivant->code} n'est pas une année suivante de ce niveau (même cycle, année " . ($annee_cycle + 1) . ").");
                    }
                }
            },
        ];
    }



    public function messages() : array {
        return [
            'cycle_id.required'     => "Le cycle est obligatoire.",
            'annee_cycle.required'  => "L'année dans le cycle est obligatoire.",
            'annee_cycle.min'       => "L'année commence à 1.",
            'code.required'         => "Le code est obligatoire.",
            'code.regex'            => "Le code doit contenir 2 à 20 lettres, chiffres ou tirets (ex. 3CI-IABD).",
            'code.unique'           => "Ce code est déjà utilisé.",
            'libelle.required'      => "Le libellé est obligatoire.",
            'nb_semestres.required' => "Le nombre de semestres est obligatoire.",
            'nb_semestres.min'      => "Au moins 1 semestre.",
            'nb_semestres.max'      => "Au plus 4 semestres.",
        ];
    }
}
