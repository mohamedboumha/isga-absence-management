<?php

namespace App\Features\PassageAnnee;

use App\Features\AnneeUniversitaire\AnneeUniversitaire;
use App\Features\Groupe\Groupe;
use App\Features\Inscription\InscriptionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PassageAnneeRequest extends FormRequest {
    public function authorize() : bool {
        return true;
    }



    public function rules() : array {
        return [
            'annee_cible_id'             => ['required', 'integer', Rule::exists('annees_universitaires', 'id')
                                                                        ->whereNull('deleted_at')],
            'groupe_id'                  => ['required', 'integer', Rule::exists('groupes', 'id')
                                                                        ->whereNull('deleted_at')],
            'destinations'               => ['array'],
            'destinations.*.niveau_id'   => ['required', 'integer', Rule::exists('niveaux_etudes', 'id')
                                                                        ->whereNull('deleted_at')],
            'destinations.*.groupe_id'   => ['nullable', 'integer', Rule::exists('groupes', 'id')
                                                                        ->whereNull('deleted_at')],
            'etudiants'                  => ['required', 'array', 'min:1'],
            'etudiants.*.inscription_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('inscriptions', 'id')
                    ->where('groupe_id', (int) $this->input('groupe_id'))
                    ->whereNull('deleted_at'),
            ],
            'etudiants.*.decision'       => ['required', Rule::in(array_keys(InscriptionService::decisions))],
            'etudiants.*.niveau_id'      => ['nullable', 'integer'],
        ];
    }



    public function after() : array {
        return [
            function (Validator $validator) {
                if ($validator->errors()
                              ->isNotEmpty()) {
                    return;
                }

                $groupe = Groupe::with(['annee_universitaire', 'niveau_etude.cycle', 'niveau_etude.suivants'])
                                ->findOrFail((int) $this->input('groupe_id'));
                $cible  = AnneeUniversitaire::findOrFail((int) $this->input('annee_cible_id'));
                $niveau = $groupe->niveau_etude;


                //======================================================================================================
                // L'année cible vient après l'année du groupe
                //======================================================================================================
                if ($cible->date_debut?->lte($groupe->annee_universitaire->date_debut)) {
                    $validator->errors()
                              ->add('annee_cible_id', "L'année cible doit être postérieure à {$groupe->annee_universitaire->libelle}.");

                    return;
                }


                //======================================================================================================
                // RG-16 : décision possible pour ce niveau, et niveau suivant prévu par le parcours
                //======================================================================================================
                $possibles    = PassageAnneeService::get_decisions_possibles($niveau);
                $suivants_ids = $niveau->suivants->pluck('id')
                                                 ->all();

                foreach ($this->input('etudiants', []) as $index => $ligne) {
                    if (!in_array($ligne['decision'], $possibles, true)) {
                        $validator->errors()
                                  ->add("etudiants.$index.decision", "Décision impossible pour un étudiant de {$niveau->code}.");
                        continue;
                    }

                    if ($ligne['decision'] !== InscriptionService::decision_admis) {
                        continue;
                    }

                    if (!$suivants_ids) {
                        $validator->errors()
                                  ->add("etudiants.$index.niveau_id", "Aucun niveau suivant n'est défini pour {$niveau->code} : complétez son parcours dans Niveaux d'études.");
                        continue;
                    }

                    $niveau_id = $ligne['niveau_id'] ?? (count($suivants_ids) === 1 ? $suivants_ids[0] : null);

                    if (!$niveau_id) {
                        $validator->errors()
                                  ->add("etudiants.$index.niveau_id", "Choisissez le niveau suivant.");
                    } elseif (!in_array((int) $niveau_id, $suivants_ids, true)) {
                        $validator->errors()
                                  ->add("etudiants.$index.niveau_id", "Ce niveau ne fait pas partie du parcours de {$niveau->code} (RG-16).");
                    }
                }


                //======================================================================================================
                // Un groupe d'accueil choisi doit être dans l'année cible et au bon niveau
                //======================================================================================================
                foreach ($this->input('destinations', []) as $index => $destination) {
                    if (empty($destination['groupe_id'])) {
                        continue;
                    }

                    $accueil = Groupe::find((int) $destination['groupe_id']);

                    if (!$accueil || $accueil->annee_universitaire_id !== $cible->id || $accueil->niveau_etude_id !== (int) $destination['niveau_id']) {
                        $validator->errors()
                                  ->add("destinations.$index.groupe_id", "Ce groupe n'est pas un groupe de ce niveau en {$cible->libelle}.");
                    }
                }
            },
        ];
    }



    public function messages() : array {
        return [
            'annee_cible_id.required'           => "L'année cible est obligatoire.",
            'etudiants.required'                => "Ce groupe n'a aucun étudiant inscrit.",
            'etudiants.*.inscription_id.exists' => "Un des étudiants n'est pas inscrit dans ce groupe.",
            'etudiants.*.decision.required'     => "Choisissez une décision.",
        ];
    }
}
