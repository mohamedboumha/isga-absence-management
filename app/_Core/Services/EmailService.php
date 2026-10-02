<?php

namespace App\_Core\Services;

use Illuminate\Support\Facades\Password;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class EmailService {
    //==================================================================================================================
    // Résultat d'un envoi
    //==================================================================================================================
    const string envoye = 'envoye';
    const string limite = 'limite';   // un lien a déjà été envoyé il y a moins d'une minute
    const string echec  = 'echec';    // serveur d'envoi injoignable, identifiants refusés...


    //==================================================================================================================
    // Lien "choisir mon mot de passe" (création de compte ou mot de passe oublié)
    // Ne lève jamais d'exception : l'échec est enregistré dans le journal de Laravel et renvoyé à l'appelant
    //==================================================================================================================
    public static function envoyer_lien_mot_de_passe(string $email) : string {
        try {
            $statut = Password::sendResetLink(['email' => $email]);
        } catch (TransportExceptionInterface $exception) {
            report($exception);

            return self::echec;
        }

        return match ($statut) {
            Password::RESET_LINK_SENT => self::envoye,
            Password::RESET_THROTTLED => self::limite,
            default                   => self::echec,
        };
    }
}
