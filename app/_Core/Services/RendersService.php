<?php

namespace App\_Core\Services;

class RendersService {
    //==================================================================================================================
    // Modes de vue (mêmes valeurs côté Vue : resources/js/_core/renders.ts)
    //==================================================================================================================
    const string mode_list         = 'list';
    const string mode_create       = 'create';
    const string mode_edit         = 'edit';
    const string mode_consultation = 'consultation';

    //==================================================================================================================
    // Types de render
    //==================================================================================================================
    const string render_chaine  = 'chaine';
    const string render_date    = 'date';
    const string render_boolean = 'boolean';
    const string render_nombre  = 'nombre';



    public static function get_mode_detail(?string $cle, ?string $mode_detail) : string {
        //==============================================================================================================
        // Pas de clé          => création
        // Clé + "edit"        => modification
        // Clé seule           => consultation
        //==============================================================================================================
        if (!$cle) {
            return self::mode_create;
        }

        if ($mode_detail === self::mode_edit) {
            return self::mode_edit;
        }

        return self::mode_consultation;
    }
}
