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
    // Types de render (= composants Vue dans resources/js/_core/renders et resources/js/_core/table)
    //==================================================================================================================
    const string render_chaine   = 'chaine';
    const string render_date     = 'date';
    const string render_boolean  = 'boolean';
    const string render_nombre   = 'nombre';
    const string render_badge    = 'badge';      // badge à la couleur enregistrée en base
    const string render_statut   = 'statut';     // pastille de statut (clés de statuts.ts)
    const string render_personne = 'personne';   // initiales + nom + seconde ligne



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
