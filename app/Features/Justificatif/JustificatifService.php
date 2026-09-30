<?php

namespace App\Features\Justificatif;

use App\_Core\Builders\Table\TableBuilder;
use App\_Core\Builders\Table\TableColumn;
use App\_Core\Services\RendersService;
use App\Features\Absence\Absence;
use App\Features\Etudiant\Etudiant;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class JustificatifService {
    //==================================================================================================================
    // Types et statuts (clé stockée => libellé affiché)
    //==================================================================================================================
    const array types = [
        'MEDICAL'       => "Médical",
        'FAMILIAL'      => "Familial",
        'ADMINISTRATIF' => "Administratif",
        'AUTRE'         => "Autre",
    ];

    const string statut_en_attente = 'EN_ATTENTE';
    const string statut_valide     = 'VALIDE';
    const string statut_refuse     = 'REFUSE';

    const array statuts = [
        self::statut_en_attente => "En attente",
        self::statut_valide     => "Validé",
        self::statut_refuse     => "Refusé",
    ];


    //==================================================================================================================
    // RG-07 : délai de dépôt après la fin de la période (72 h par défaut)
    //==================================================================================================================
    const int delai_depot_jours = 3;


    //==================================================================================================================
    // Fichiers : disque privé (storage/app/private), jamais public
    //==================================================================================================================
    const string disque        = 'local';
    const string dossier       = 'justificatifs';
    const array  extensions    = ['pdf', 'jpg', 'jpeg', 'png'];
    const int    taille_max_ko = 5120; // 5 Mo


    //==================================================================================================================
    // Tableau de la liste, filtrable par statut (BF-22)
    //==================================================================================================================
    public static function get_table(?string $statut) : array {
        $query = Justificatif
            ::query()
            ->with('etudiant')
            ->when(
                $statut && array_key_exists($statut, self::statuts),
                fn($query) => $query->where('statut', $statut)
            );

        return TableBuilder
            ::new($query)
            ->add_column(
                TableColumn
                    ::new()
                    ->label("Étudiant")
                    ->nom_colonne('etudiant.nom_complet')
                    ->render(RendersService::render_chaine)
            )
            ->add_column(
                TableColumn
                    ::new()
                    ->label("CNE")
                    ->nom_colonne('etudiant.cne')
                    ->render(RendersService::render_chaine)
            )
            ->add_column(
                TableColumn
                    ::new()
                    ->label("Type")
                    ->nom_colonne('type_render')
                    ->render(RendersService::render_chaine)
            )
            ->add_column(
                TableColumn
                    ::new()
                    ->label("Période")
                    ->nom_colonne('periode_render')
                    ->render(RendersService::render_chaine)
            )
            ->add_column(
                TableColumn
                    ::new()
                    ->label("Déposé le")
                    ->nom_colonne('date_depot')
                    ->render(RendersService::render_date)
                    ->triable()
            )
            ->add_column(
                TableColumn
                    ::new()
                    ->label("Hors délai")
                    ->nom_colonne('hors_delai')
                    ->render(RendersService::render_boolean)
            )
            ->add_column(
                TableColumn
                    ::new()
                    ->label("Statut")
                    ->nom_colonne('statut_render')
                    ->render(RendersService::render_chaine)
            )
            ->default_tri('date_depot', 'desc')
            ->row_url(fn(Justificatif $justificatif) => route('justificatif.detail', ['cle' => $justificatif->cle]))
            ->get();
    }



    public static function get_compteurs() : array {
        //==============================================================================================================
        // Nombre de justificatifs par statut (pour les onglets)
        //==============================================================================================================
        return Justificatif
            ::query()
            ->selectRaw('statut, count(*) as total')
            ->groupBy('statut')
            ->pluck('total', 'statut')
            ->all();
    }



    public static function get_selects() : array {
        return [
            'etudiants' => Etudiant
                ::query()
                ->with('groupe')
                ->orderBy('nom')
                ->orderBy('prenom')
                ->get()
                ->map(fn(Etudiant $etudiant) => [
                    'valeur' => $etudiant->id,
                    'label'  => "{$etudiant->nom_complet} — {$etudiant->cne} ({$etudiant->groupe->nom})",
                ])
                ->all(),
            'types'     => array_map(
                fn(string $cle, string $label) => ['valeur' => $cle, 'label' => $label],
                array_keys(self::types),
                self::types
            ),
        ];
    }



    //==================================================================================================================
    // Création / modification, avec remplacement éventuel du fichier
    //==================================================================================================================
    public static function process_update_or_create(?string $cle, array $attributes, ?UploadedFile $fichier) : Justificatif {
        unset($attributes['fichier']);

        $justificatif = Justificatif::get_by_cle_or_new($cle);
        $justificatif->fill($attributes);

        if (!$justificatif->exists) {
            $justificatif->date_depot = today();
            $justificatif->statut     = self::statut_en_attente;
        }

        if ($fichier) {
            $ancien_chemin = $justificatif->fichier_chemin;

            $justificatif->fichier_chemin = $fichier->store(self::dossier, self::disque);
            $justificatif->fichier_nom    = $fichier->getClientOriginalName();

            if ($ancien_chemin) {
                Storage::disk(self::disque)
                       ->delete($ancien_chemin);
            }
        }

        $justificatif->save();

        return $justificatif;
    }



    //==================================================================================================================
    // Validation : les absences de la période deviennent justifiées (RG-05)
    //==================================================================================================================
    public static function valider(Justificatif $justificatif, User $user) : int {
        self::verifier_en_attente($justificatif);

        return DB::transaction(function () use ($justificatif, $user) {
            $justificatif->statut      = self::statut_valide;
            $justificatif->motif_refus = null;
            $justificatif->traite_par  = $user->id;
            $justificatif->traite_le   = now();
            $justificatif->save();

            $absences = $justificatif->get_absences_couvertes();

            $absences->each(fn(Absence $absence) => $absence->forceFill(
                [
                    'justifiee'       => true,
                    'justificatif_id' => $justificatif->id,
                ])
                                                            ->save());

            return $absences->count();
        });
    }



    //==================================================================================================================
    // Refus : motif obligatoire (RG-06), les absences restent non justifiées
    //==================================================================================================================
    public static function refuser(Justificatif $justificatif, string $motif_refus, User $user) : void {
        self::verifier_en_attente($justificatif);

        $justificatif->statut      = self::statut_refuse;
        $justificatif->motif_refus = $motif_refus;
        $justificatif->traite_par  = $user->id;
        $justificatif->traite_le   = now();
        $justificatif->save();
    }



    //==================================================================================================================
    // Justificatif validé couvrant une date (utilisé quand l'appel est fait après la validation)
    //==================================================================================================================
    public static function get_justificatif_valide_pour(int $etudiant_id, string $date) : ?Justificatif {
        return Justificatif
            ::query()
            ->where('etudiant_id', $etudiant_id)
            ->where('statut', self::statut_valide)
            ->whereDate('date_debut', '<=', $date)
            ->whereDate('date_fin', '>=', $date)
            ->first();
    }



    public static function process_delete(string $cle) : void {
        $justificatif = self::get_or_fail($cle);

        if (!$justificatif->can_be_deleted()) {
            throw ValidationException::withMessages([
                                                        'justificatif' => "Un justificatif déjà traité ne peut pas être supprimé.",
                                                    ]);
        }

        $justificatif->delete();
    }



    public static function get_or_fail(string $cle) : Justificatif {
        $justificatif = Justificatif::get_by_cle($cle);

        abort_if(!$justificatif, 404, "Justificatif introuvable");

        return $justificatif;
    }



    protected static function verifier_en_attente(Justificatif $justificatif) : void {
        if (!$justificatif->is_en_attente()) {
            throw ValidationException::withMessages([
                                                        'justificatif' => "Ce justificatif a déjà été traité ({$justificatif->statut_render}).",
                                                    ]);
        }
    }
}
