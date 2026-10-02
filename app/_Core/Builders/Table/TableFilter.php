<?php

namespace App\_Core\Builders\Table;

use Closure;
use Illuminate\Database\Eloquent\Builder;

class TableFilter {
    const string type_select  = 'select';
    const string type_periode = 'periode';
    const string type_oui_non = 'oui_non';

    protected string   $nom;
    protected string   $label     = '';
    protected string   $type      = self::type_select;
    protected array    $options   = [];
    protected ?string  $colonne   = null;
    protected ?Closure $appliquer = null;
    protected string   $label_oui = "Oui";
    protected string   $label_non = "Non";



    //==================================================================================================================
    // Le nom sert de clé dans l'URL : ?filtres[groupe]=12
    //==================================================================================================================
    public static function new(string $nom) : static {
        $filtre = new static();

        $filtre->nom = $nom;

        return $filtre;
    }



    public function label(string $label) : static {
        $this->label = $label;

        return $this;
    }



    //==================================================================================================================
    // Liste de choix : [['valeur' => ..., 'label' => ...], ...]
    //==================================================================================================================
    public function select(array $options) : static {
        $this->type    = self::type_select;
        $this->options = $options;

        return $this;
    }



    //==================================================================================================================
    // Deux dates facultatives (du / au)
    //==================================================================================================================
    public function periode() : static {
        $this->type = self::type_periode;

        return $this;
    }



    public function oui_non(string $label_oui = "Oui", string $label_non = "Non") : static {
        $this->type      = self::type_oui_non;
        $this->label_oui = $label_oui;
        $this->label_non = $label_non;

        return $this;
    }



    //==================================================================================================================
    // Filtre simple sur une colonne de la table (égalité, intervalle de dates, booléen)
    //==================================================================================================================
    public function sur_colonne(string $colonne) : static {
        $this->colonne = $colonne;

        return $this;
    }



    //==================================================================================================================
    // Filtre sur mesure : fn(Builder $query, mixed $valeur) => ...
    //==================================================================================================================
    public function appliquer(Closure $appliquer) : static {
        $this->appliquer = $appliquer;

        return $this;
    }



    public function get_nom() : string {
        return $this->nom;
    }



    //==================================================================================================================
    // Valeur de l'URL => valeur propre, ou null si le filtre n'est pas utilisé (ou invalide)
    //==================================================================================================================
    public function normaliser(mixed $brut) : mixed {
        switch ($this->type) {
            case self::type_periode:
                $du = is_array($brut) ? self::date_valide($brut['du'] ?? null) : null;
                $au = is_array($brut) ? self::date_valide($brut['au'] ?? null) : null;

                return $du || $au ? ['du' => $du, 'au' => $au] : null;

            case self::type_oui_non:
                return match ((string) $brut) {
                    '1'     => true,
                    '0'     => false,
                    default => null,
                };

            default:
                return is_scalar($brut) && (string) $brut !== '' ? (string) $brut : null;
        }
    }



    public function appliquer_sur(Builder $query, mixed $valeur) : void {
        if ($this->appliquer) {
            ($this->appliquer)($query, $valeur);

            return;
        }

        if (!$this->colonne) {
            return;
        }

        switch ($this->type) {
            case self::type_periode:
                $query
                    ->when($valeur['du'], fn(Builder $query, string $du) => $query->whereDate($this->colonne, '>=', $du))
                    ->when($valeur['au'], fn(Builder $query, string $au) => $query->whereDate($this->colonne, '<=', $au));
                break;

            default:
                $query->where($this->colonne, $valeur);
        }
    }



    //==================================================================================================================
    // Pour Vue : définition du filtre + sa valeur actuelle
    //==================================================================================================================
    public function get(mixed $valeur) : array {
        $options = $this->type === self::type_oui_non
            ? [['valeur' => '1', 'label' => $this->label_oui], ['valeur' => '0', 'label' => $this->label_non]]
            : $this->options;

        return [
            'nom'     => $this->nom,
            'label'   => $this->label,
            'type'    => $this->type,
            'options' => $options,
            'valeur'  => is_bool($valeur) ? ($valeur ? '1' : '0') : $valeur,
        ];
    }



    protected static function date_valide(mixed $date) : ?string {
        return is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : null;
    }
}
