<?php

namespace App\Features\Enseignant;

use App\_Core\Builders\Table\TableBuilder;
use App\_Core\Builders\Table\TableColumn;
use App\_Core\Services\RendersService;
use App\Features\Module\Module;
use App\Features\User\UserService;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EnseignantService {
    //==================================================================================================================
    // Tableau de la liste (mode_list)
    //==================================================================================================================
    public static function get_table() : array {
        return TableBuilder
            ::new(Enseignant::query()
                            ->with('user'))
            ->add_column(
                TableColumn
                    ::new()
                    ->label("Nom")
                    ->nom_colonne('nom')
                    ->render(RendersService::render_chaine)
                    ->triable()
                    ->cherchable()
            )
            ->add_column(
                TableColumn
                    ::new()
                    ->label("Prénom")
                    ->nom_colonne('prenom')
                    ->render(RendersService::render_chaine)
                    ->triable()
                    ->cherchable()
            )
            ->add_column(
                TableColumn
                    ::new()
                    ->label("E-mail")
                    ->nom_colonne('email')
                    ->render(RendersService::render_chaine)
                    ->triable()
                    ->cherchable()
            )
            ->add_column(
                TableColumn
                    ::new()
                    ->label("Compte actif")
                    ->nom_colonne('user.actif')
                    ->render(RendersService::render_boolean)
            )
            ->default_tri('nom')
            ->row_url(fn(Enseignant $enseignant) => route('enseignant.detail', ['cle' => $enseignant->cle]))
            ->get();
    }



    //==================================================================================================================
    // Modules pour le multi-select : "GI-BDD — Bases de données"
    //==================================================================================================================
    public static function get_modules_pour_select() : array {
        return Module
            ::query()
            ->orderBy('code')
            ->get()
            ->map(fn(Module $module) => [
                'valeur' => $module->id,
                'label'  => "{$module->code} — {$module->intitule}",
            ])
            ->all();
    }



    //==================================================================================================================
    // Création / modification : fiche + compte + modules, dans une seule transaction
    //==================================================================================================================
    public static function process_update_or_create(?string $cle, array $attributes) : Enseignant {
        $module_ids = $attributes['modules'] ?? [];
        unset($attributes['modules']);

        $nouveau_compte = false;

        $enseignant = DB::transaction(function () use ($cle, $attributes, $module_ids, &$nouveau_compte) {
            //==========================================================================================================
            // Fiche enseignant
            //==========================================================================================================
            $enseignant = Enseignant::get_by_cle_or_new($cle);
            $enseignant->fill($attributes);


            //==========================================================================================================
            // Compte de connexion : créé la première fois, synchronisé ensuite
            //==========================================================================================================
            $user = $enseignant->user ?? new User();

            $user->forceFill([
                                 'name'   => $attributes['nom'],
                                 'prenom' => $attributes['prenom'],
                                 'email'  => $attributes['email'],
                             ]);

            if (!$user->exists) {
                $user->forceFill([
                                     'password'          => Str::random(40), // provisoire : l'enseignant choisit le sien via le lien
                                     'role'              => UserService::role_enseignant,
                                     'actif'             => true,
                                     'email_verified_at' => now(),
                                 ]);

                $nouveau_compte = true;
            }

            $user->save();

            $enseignant->user_id = $user->id;
            $enseignant->save();


            //==========================================================================================================
            // Modules enseignés
            //==========================================================================================================
            $enseignant->modules()
                       ->sync($module_ids);

            return $enseignant;
        });


        //==============================================================================================================
        // Nouveau compte : envoi du lien "choisir mon mot de passe" (après la transaction)
        //==============================================================================================================
        if ($nouveau_compte) {
            Password::sendResetLink(['email' => $enseignant->email]);
        }

        return $enseignant;
    }



    //==================================================================================================================
    // Suppression : fiche archivée (soft delete), compte désactivé (RG-14)
    //==================================================================================================================
    public static function process_delete(string $cle) : void {
        $enseignant = self::get_or_fail($cle);

        if (!$enseignant->can_be_deleted()) {
            throw ValidationException::withMessages([
                                                        'enseignant' => "L'enseignant {$enseignant->nom_complet} ne peut pas être supprimé (il a des séances).",
                                                    ]);
        }

        DB::transaction(function () use ($enseignant) {
            $enseignant->user?->forceFill(['actif' => false])
                             ->save();
            $enseignant->delete();
        });
    }



    public static function get_or_fail(string $cle) : Enseignant {
        $enseignant = Enseignant::get_by_cle($cle);

        abort_if(!$enseignant, 404, "Enseignant introuvable");

        return $enseignant;
    }
}
