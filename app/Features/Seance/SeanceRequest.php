<?php

namespace App\Features\Seance;

use App\Features\Enseignant\Enseignant;
use App\Features\Filiere\FiliereService;
use App\Features\Groupe\Groupe;
use App\Features\Module\Module;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SeanceRequest extends FormRequest {
    public function authorize() : bool {
        return true;
    }



    protected function prepareForValidation() : void {
        $this->merge([
                         'salle'   => strtoupper(trim((string) $this->input('salle'))) ?: null,
                         'annulee' => $this->boolean('annulee'),
                     ]);
    }



    public function rules() : array {
        return [
            'module_id'     => ['required', Rule::exists('modules', 'id')
                                                ->whereNull('deleted_at')],
            'enseignant_id' => ['required', Rule::exists('enseignants', 'id')
                                                ->whereNull('deleted_at')],
            'groupe_id'     => ['required', Rule::exists('groupes', 'id')
                                                ->whereNull('deleted_at')],
            'date'          => ['required', 'date'],
            'heure_debut'   => ['required', 'date_format:H:i'],
            'heure_fin'     => ['required', 'date_format:H:i', 'after:heure_debut'],
            'type'          => ['required', Rule::in(SeanceService::types)],
            'salle'         => ['nullable', 'max:30'],
            'annulee'       => ['boolean'],
        ];
    }



    public function after() : array {
        return [
            function (Validator $validator) {
                if ($validator->errors()
                              ->isNotEmpty()) {
                    return;
                }

                $cle        = $this->route('cle');
                $module     = Module::findOrFail((int) $this->input('module_id'));
                $enseignant = Enseignant::findOrFail((int) $this->input('enseignant_id'));
                $groupe     = Groupe::with('annee_universitaire')
                                    ->findOrFail((int) $this->input('groupe_id'));


                //======================================================================================================
                // Appel déjà fait : le groupe ne peut plus changer (les absences concernent ses étudiants)
                //======================================================================================================
                $seance_existante = $cle ? Seance::get_by_cle($cle) : null;

                if ($seance_existante?->appel_fait && $seance_existante->groupe_id !== $groupe->id) {
                    $validator->errors()
                              ->add('groupe_id', "L'appel de cette séance est déjà fait : le groupe ne peut plus être modifié.");
                }


                //======================================================================================================
                // L'enseignant enseigne ce module
                //======================================================================================================
                if (!$enseignant->modules()
                                ->whereKey($module->id)
                                ->exists()) {
                    $validator->errors()
                              ->add('enseignant_id', "{$enseignant->nom_complet} n'enseigne pas le module {$module->code}.");
                }


                //======================================================================================================
                // Le module correspond à la filière et au niveau du groupe
                //======================================================================================================
                if ($module->filiere_id !== $groupe->filiere_id) {
                    $validator->errors()
                              ->add('module_id', "Le module {$module->code} n'appartient pas à la filière du groupe {$groupe->nom}.");
                } elseif (FiliereService::get_niveau_by_semestre($module->semestre) !== $groupe->niveau) {
                    $validator->errors()
                              ->add('module_id', "Le module {$module->code} ({$module->semestre}) ne correspond pas au niveau {$groupe->niveau} du groupe.");
                }


                //======================================================================================================
                // La date est dans l'année universitaire du groupe
                //======================================================================================================
                $annee = $groupe->annee_universitaire;
                $date  = (string) $this->input('date');

                if ($annee->date_debut && $annee->date_fin
                    && ($date < $annee->date_debut->format('Y-m-d') || $date > $annee->date_fin->format('Y-m-d'))) {
                    $validator->errors()
                              ->add('date', "La date doit être dans l'année {$annee->libelle} ({$annee->periode_render}).");
                }


                //======================================================================================================
                // Conflits d'horaire (sauf si la séance est annulée)
                //======================================================================================================
                if ($this->boolean('annulee')) {
                    return;
                }

                $heure_debut = (string) $this->input('heure_debut');
                $heure_fin   = (string) $this->input('heure_fin');

                $conflit_groupe = SeanceService::get_seance_en_conflit('groupe_id', $groupe->id, $date, $heure_debut, $heure_fin, $cle);

                if ($conflit_groupe) {
                    $validator->errors()
                              ->add('heure_debut', "Le groupe {$groupe->nom} a déjà une séance de {$conflit_groupe->horaire_render} ce jour-là.");
                }

                $conflit_enseignant = SeanceService::get_seance_en_conflit('enseignant_id', $enseignant->id, $date, $heure_debut, $heure_fin, $cle);

                if ($conflit_enseignant) {
                    $validator->errors()
                              ->add('enseignant_id', "{$enseignant->nom_complet} a déjà une séance de {$conflit_enseignant->horaire_render} ce jour-là.");
                }
            },
        ];
    }



    public function messages() : array {
        return [
            'module_id.required'      => "Le module est obligatoire.",
            'enseignant_id.required'  => "L'enseignant est obligatoire.",
            'groupe_id.required'      => "Le groupe est obligatoire.",
            'date.required'           => "La date est obligatoire.",
            'heure_debut.required'    => "L'heure de début est obligatoire.",
            'heure_debut.date_format' => "L'heure de début doit avoir le format HH:MM.",
            'heure_fin.required'      => "L'heure de fin est obligatoire.",
            'heure_fin.date_format'   => "L'heure de fin doit avoir le format HH:MM.",
            'heure_fin.after'         => "L'heure de fin doit être après l'heure de début.",
            'type.required'           => "Le type est obligatoire.",
            'type.in'                 => "Le type doit être COURS, TD ou TP.",
            'salle.max'               => "La salle ne doit pas dépasser 30 caractères.",
        ];
    }
}
