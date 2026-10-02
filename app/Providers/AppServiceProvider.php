<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider {
    /**
     * Register any application services.
     */
    public function register() : void {
        //
    }



    /**
     * Bootstrap any application services.
     */
    public function boot() : void {
        $this->configureDefaults();
        $this->configurerEmailMotDePasse();
    }



    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults() : void {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn() : ?Password => app()->isProduction()
            ? Password::min(12)
                      ->mixedCase()
                      ->letters()
                      ->numbers()
                      ->symbols()
                      ->uncompromised()
            : null,
        );
    }

    //==================================================================================================================
    // E-mail "choisir son mot de passe" : envoyé à la création d'un compte ET pour un mot de passe oublié,
    // d'où une formulation qui convient aux deux cas
    //==================================================================================================================
    protected function configurerEmailMotDePasse() : void {
        ResetPassword::toMailUsing(function (object $utilisateur, string $token) : MailMessage {
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $utilisateur->getEmailForPasswordReset(),
            ],               false));

            $minutes = config('auth.passwords.' . config('auth.defaults.passwords') . '.expire');

            $prenom = $utilisateur->prenom ?? null;

            return (new MailMessage)
                ->subject("Choisissez votre mot de passe — ISGA Absences")
                ->greeting($prenom ? "Bonjour {$prenom}," : "Bonjour,")
                ->line("Pour vous connecter à l'application de gestion des absences de l'ISGA, choisissez votre mot de passe en cliquant sur le bouton ci-dessous.")
                ->action("Choisir mon mot de passe", $url)
                ->line("Ce lien est valable {$minutes} minutes. Passé ce délai, utilisez « Mot de passe oublié ? » sur la page de connexion pour en recevoir un nouveau.")
                ->line("Si vous n'êtes pas à l'origine de cette demande, ignorez simplement ce message : votre mot de passe actuel reste inchangé.")
                ->salutation("ISGA");
        });
    }
}
