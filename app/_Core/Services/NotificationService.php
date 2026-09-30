<?php

namespace App\_Core\Services;

use Inertia\Inertia;

class NotificationService {
    //==================================================================================================================
    // Types attendus par vue-sonner côté Vue (toast.success, toast.error...)
    //==================================================================================================================
    const string type_succes        = 'success';
    const string type_erreur        = 'error';
    const string type_info          = 'info';
    const string type_avertissement = 'warning';



    public static function succes(string $message) : void {
        self::envoyer(self::type_succes, $message);
    }



    public static function erreur(string $message) : void {
        self::envoyer(self::type_erreur, $message);
    }



    public static function info(string $message) : void {
        self::envoyer(self::type_info, $message);
    }



    public static function avertissement(string $message) : void {
        self::envoyer(self::type_avertissement, $message);
    }



    //==================================================================================================================
    // Message "flash" : affiché une seule fois, sur la page suivante (après la redirection)
    //==================================================================================================================
    protected static function envoyer(string $type, string $message) : void {
        Inertia::flash('toast', ['type' => $type, 'message' => $message]);
    }
}
