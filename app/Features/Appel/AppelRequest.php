<?php

namespace App\Features\Appel;

use App\Features\Seance\Seance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AppelRequest extends FormRequest {
    public function authorize() : bool {
        return true; // les droits sont vérifiés dans le controller (AppelService)
    }



    protected function prepareForValidation() : void {
        //==============================================================================================================
        // Tous présents : aucune absence envoyée
        //==============================================================================================================
        $this->merge([
                         'absences' => $this->input('absences', []),
                     ]);
    }



    public function rules() : array {
        $seance = Seance::get_by_cle($this->route('cle'));

        return [
            'absences'               => ['array'],
            'absences.*.etudiant_id' => [
                'required',
                'integer',
                'distinct',
                //======================================================================================================
                // RG-04 : uniquement des étudiants du groupe de la séance
                //======================================================================================================
                Rule::exists('inscriptions', 'etudiant_id')
                    ->where('groupe_id', $seance?->groupe_id)
                    ->whereNull('deleted_at'),
            ],
            'absences.*.remarque'    => ['nullable', 'string', 'max:255'],
        ];
    }



    public function messages() : array {
        return [
            'absences.*.etudiant_id.exists'   => "Un des étudiants n'est pas inscrit dans le groupe de cette séance.",
            'absences.*.etudiant_id.distinct' => "Un étudiant apparaît deux fois dans l'appel.",
            'absences.*.remarque.max'         => "Une remarque ne doit pas dépasser 255 caractères.",
        ];
    }
}
