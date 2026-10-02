<?php

namespace App\Features\Etudiant;

use App\Features\Export\ExportService;
use App\Features\Justificatif\Justificatif;
use App\Features\Justificatif\JustificatifService;

class EtudiantConsultationService {
    //==================================================================================================================
    // Tout ce que la fiche de l'étudiant affiche en consultation (sur l'année active)
    //==================================================================================================================
    public static function get(Etudiant $etudiant) : array {
        //==============================================================================================================
        // Mêmes calculs que le relevé PDF (RG-08)
        //==============================================================================================================
        $periode = ExportService::get_periode(null, null);
        $releve  = ExportService::get_releve_etudiant($etudiant, $periode);

        $inscription = $etudiant
            ->inscription_active()
            ->with(['groupe.niveau_etude.cycle', 'groupe.niveau_etude.filiere.cycle'])
            ->first();


        //==============================================================================================================
        // Justificatifs : les 5 derniers déposés
        //==============================================================================================================
        $justificatifs = Justificatif
            ::query()
            ->where('etudiant_id', $etudiant->id)
            ->orderByDesc('date_depot')
            ->limit(5)
            ->get();

        $nb_en_attente = Justificatif
            ::query()
            ->where('etudiant_id', $etudiant->id)
            ->where('statut', JustificatifService::statut_en_attente)
            ->count();

        return [
            'resume'     => [
                'groupe'                   => $inscription ? [
                    'nom'     => $inscription->groupe->nom,
                    'niveau'  => $inscription->groupe->niveau_etude->libelle,
                    'couleur' => $inscription->groupe->niveau_etude->couleur_effective,
                    'url'     => route('groupe.detail', ['cle' => $inscription->groupe->cle]),
                ] : null,
                'periode'                  => $releve['periode'],
                'nb_absences'              => $releve['total']['nb_absences'],
                'nb_justifiees'            => $releve['total']['nb_justifiees'],
                'nb_non_justifiees'        => $releve['total']['nb_absences'] - $releve['total']['nb_justifiees'],
                'heures'                   => $releve['total']['heures'],
                'taux'                     => $releve['total']['taux'],
                'justificatifs_en_attente' => $nb_en_attente,
            ],

            //==========================================================================================================
            // Calendrier de présence de l'année (null si non inscrit)
            //==========================================================================================================
            'calendrier' => EtudiantCalendrierService::get($etudiant),

            //==========================================================================================================
            // Les 8 absences les plus récentes de l'année
            //==========================================================================================================
            'absences'   => array_slice(array_reverse($releve['absences']), 0, 8),

            'justificatifs' => $justificatifs->map(fn(Justificatif $justificatif) => [
                'type'       => $justificatif->type_render,
                'periode'    => $justificatif->periode_render,
                'date_depot' => $justificatif->date_depot?->format('d/m/Y'),
                'statut'     => $justificatif->statut,
                'hors_delai' => $justificatif->hors_delai,
                'url'        => route('justificatif.detail', ['cle' => $justificatif->cle]),
            ])
                                             ->all(),

            'liens' => [
                'justificatifs' => route('justificatifs.list', ['statut' => 'TOUS', 'search' => $etudiant->cne]),
            ],
        ];
    }
}
