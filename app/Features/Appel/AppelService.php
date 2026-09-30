<?php

namespace App\Features\Appel;

use App\_Core\Builders\Table\TableBuilder;
use App\_Core\Builders\Table\TableColumn;
use App\_Core\Services\RendersService;
use App\Features\Absence\Absence;
use App\Features\Enseignant\Enseignant;
use App\Features\Etudiant\Etudiant;
use App\Features\Seance\Seance;
use App\Features\User\UserService;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Features\Justificatif\JustificatifService;

class AppelService {
    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    //
    // DROITS (RG-12, RG-13)
    //
    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    public static function is_administration(User $user) : bool {
        return in_array($user->role, UserService::roles_administration, true);
    }



    public static function is_enseignant_de_la_seance(User $user, Seance $seance) : bool {
        return $seance->enseignant->user_id === $user->id;
    }



    public static function can_voir(User $user, Seance $seance) : bool {
        return self::is_administration($user) || self::is_enseignant_de_la_seance($user, $seance);
    }



    //==================================================================================================================
    // null = modification autorisée ; sinon, la raison du refus (affichée à l'utilisateur)
    //==================================================================================================================
    public static function get_refus_modification(User $user, Seance $seance) : ?string {
        if ($seance->annulee) {
            return "Cette séance est annulée.";
        }

        if ($seance->date?->isAfter(today())) {
            return "L'appel ne peut être fait qu'à partir du jour de la séance.";
        }

        if (self::is_administration($user)) {
            return null;
        }

        if (!self::is_enseignant_de_la_seance($user, $seance)) {
            return "Vous n'êtes pas l'enseignant de cette séance.";
        }

        if ($seance->date && now()->isAfter($seance->date->copy()
                                                         ->endOfDay())) {
            return "Le délai de modification est dépassé (fin du jour de la séance). Contactez l'administration pour toute correction.";
        }

        return null;
    }



    public static function get_enseignant_connecte(User $user) : Enseignant {
        $enseignant = $user->enseignant;

        abort_if(!$enseignant, 403, "Aucune fiche enseignant n'est liée à ce compte.");

        return $enseignant;
    }



    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    //
    // DONNÉES DE LA PAGE D'APPEL
    //
    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    public static function get_seance_infos(Seance $seance) : array {
        $seance->loadMissing(['module', 'groupe', 'enseignant', 'appel_fait_par_user']);

        return [
            'cle'            => $seance->cle,
            'date'           => $seance->date?->format('d/m/Y'),
            'horaire'        => $seance->horaire_render,
            'module'         => "{$seance->module->code} — {$seance->module->intitule}",
            'groupe'         => $seance->groupe->nom,
            'enseignant'     => $seance->enseignant->nom_complet,
            'type'           => $seance->type,
            'salle'          => $seance->salle,
            'annulee'        => $seance->annulee,
            'appel_fait_le'  => $seance->appel_fait_le?->format('d/m/Y à H:i'),
            'appel_fait_par' => $seance->appel_fait_par_user?->name,
        ];
    }



    //==================================================================================================================
    // Étudiants du groupe, avec leur absence éventuelle pour cette séance
    //==================================================================================================================
    public static function get_etudiants_appel(Seance $seance) : array {
        $absences = $seance
            ->absences()
            ->get()
            ->keyBy('etudiant_id');

        return Etudiant
            ::query()
            ->where('groupe_id', $seance->groupe_id)
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get()
            ->map(fn(Etudiant $etudiant) => [
                'etudiant_id' => $etudiant->id,
                'cne'         => $etudiant->cne,
                'nom_complet' => $etudiant->nom_complet,
                'absent'      => $absences->has($etudiant->id),
                'justifiee'   => (bool) $absences->get($etudiant->id)?->justifiee,
                'remarque'    => $absences->get($etudiant->id)?->remarque,
            ])
            ->all();
    }



    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    //
    // ENREGISTREMENT
    //
    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    public static function enregistrer_appel(Seance $seance, array $absences, User $user) : void {
        DB::transaction(function () use ($seance, $absences, $user) {
            $ids_absents = array_map(fn(array $absence) => (int) $absence['etudiant_id'], $absences);


            //==========================================================================================================
            // Étudiants redevenus présents : absence archivée (soft delete)
            //==========================================================================================================
            Absence
                ::query()
                ->where('seance_id', $seance->id)
                ->when($ids_absents, fn($query) => $query->whereNotIn('etudiant_id', $ids_absents))
                ->get()
                ->each(fn(Absence $absence) => $absence->delete());

            //==========================================================================================================
            // Étudiants absents : création, ou restauration d'une absence archivée
            //==========================================================================================================
            foreach ($absences as $ligne) {
                $absence = Absence
                    ::withTrashed()
                    ->firstOrNew([
                                     'seance_id'   => $seance->id,
                                     'etudiant_id' => (int) $ligne['etudiant_id'],
                                 ]);

                if ($absence->trashed()) {
                    $absence->restore();
                }

                $absence->remarque   = $ligne['remarque'] ?? null;
                $absence->saisie_par = $user->id;

                //======================================================================================================
                // Un justificatif déjà validé couvre cette date : l'absence est justifiée d'office
                //======================================================================================================
                $justificatif = JustificatifService::get_justificatif_valide_pour($absence->etudiant_id, $seance->date?->format('Y-m-d') ?? '');

                $absence->justifiee       = $justificatif !== null;
                $absence->justificatif_id = $justificatif?->id;
                $absence->save();
            }


            //==========================================================================================================
            // Première fois : on note quand et par qui l'appel a été fait
            //==========================================================================================================
            if (!$seance->appel_fait_le) {
                $seance->appel_fait_le  = now();
                $seance->appel_fait_par = $user->id;
                $seance->save();
            }
        });
    }



    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    //
    // "MES SÉANCES" (enseignant)
    //
    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    public static function get_table_mes_seances(Enseignant $enseignant) : array {
        return TableBuilder
            ::new(Seance::query()
                        ->where('enseignant_id', $enseignant->id)
                        ->with(['module', 'groupe']))
            ->add_column(
                TableColumn
                    ::new()
                    ->label("Date")
                    ->nom_colonne('date')
                    ->render(RendersService::render_date)
                    ->triable()
            )
            ->add_column(
                TableColumn
                    ::new()
                    ->label("Horaire")
                    ->nom_colonne('horaire_render')
                    ->render(RendersService::render_chaine)
            )
            ->add_column(
                TableColumn
                    ::new()
                    ->label("Module")
                    ->nom_colonne('module.code')
                    ->render(RendersService::render_chaine)
            )
            ->add_column(
                TableColumn
                    ::new()
                    ->label("Groupe")
                    ->nom_colonne('groupe.nom')
                    ->render(RendersService::render_chaine)
            )
            ->add_column(
                TableColumn
                    ::new()
                    ->label("Type")
                    ->nom_colonne('type')
                    ->render(RendersService::render_chaine)
            )
            ->add_column(
                TableColumn
                    ::new()
                    ->label("Statut")
                    ->nom_colonne('statut_render')
                    ->render(RendersService::render_chaine)
            )
            ->add_column(
                TableColumn
                    ::new()
                    ->label("Appel fait")
                    ->nom_colonne('appel_fait')
                    ->render(RendersService::render_boolean)
            )
            ->default_tri('date', 'desc')
            ->row_url(fn(Seance $seance) => route('appel.detail', ['cle' => $seance->cle]))
            ->get();
    }
}
