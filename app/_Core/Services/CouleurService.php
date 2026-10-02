<?php

namespace App\_Core\Services;

class CouleurService {
    //==================================================================================================================
    // Palette des couleurs d'entités (cycles, filières) : 16 teintes lisibles sur fond blanc et en mode sombre
    //==================================================================================================================
    const array palette = [
        '#1E3A8A' => "Marine",
        '#0369A1' => "Bleu",
        '#0E7490' => "Cyan",
        '#0F766E' => "Sarcelle",
        '#15803D' => "Vert",
        '#4D7C0F' => "Olive",
        '#A16207' => "Sable",
        '#B45309' => "Ambre",
        '#C2410C' => "Orange",
        '#9A3412' => "Brique",
        '#BE185D' => "Rose",
        '#86198F' => "Prune",
        '#7C3AED' => "Violet",
        '#4F46E5' => "Indigo",
        '#475569' => "Ardoise",
        '#3F3F46' => "Graphite",
    ];

    const string couleur_defaut = '#475569';



    public static function get_valeurs() : array {
        return array_keys(self::palette);
    }



    //==================================================================================================================
    // Pour le sélecteur Vue : [{ valeur: '#0369A1', label: 'Bleu' }, ...]
    //==================================================================================================================
    public static function get_palette_pour_front() : array {
        return array_map(
            fn(string $valeur, string $label) => ['valeur' => $valeur, 'label' => $label],
            array_keys(self::palette),
            self::palette
        );
    }
}
