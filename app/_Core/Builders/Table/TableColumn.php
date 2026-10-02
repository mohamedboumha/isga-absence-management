<?php

namespace App\_Core\Builders\Table;

use App\_Core\Services\RendersService;
use Closure;
use Illuminate\Database\Eloquent\Model;

class TableColumn {
    protected string   $label              = '';
    protected string   $nom_colonne        = '';
    protected string   $render             = RendersService::render_chaine;
    protected bool     $triable            = false;
    protected bool     $cherchable         = false;
    protected ?string  $colonne_tri        = null;
    protected array    $colonnes_recherche = [];
    protected ?string  $couleur            = null;
    protected ?string  $sous_texte         = null;
    protected ?Closure $valeur             = null;



    public static function new() : static {
        return new static();
    }



    public function label(string $label) : static {
        $this->label = $label;

        return $this;
    }



    public function nom_colonne(string $nom_colonne) : static {
        $this->nom_colonne = $nom_colonne;

        return $this;
    }



    public function render(string $render) : static {
        $this->render = $render;

        return $this;
    }



    public function triable(bool $triable = true) : static {
        $this->triable = $triable;

        return $this;
    }



    public function cherchable(bool $cherchable = true) : static {
        $this->cherchable = $cherchable;

        return $this;
    }



    //==================================================================================================================
    // Colonne calculée (ex. nom_complet) : trier sur une vraie colonne de la table (ex. nom)
    //==================================================================================================================
    public function tri_sur(string $colonne) : static {
        $this->colonne_tri = $colonne;
        $this->triable     = true;

        return $this;
    }



    //==================================================================================================================
    // Chercher dans plusieurs colonnes, y compris d'une relation (ex. "etudiant.nom")
    //==================================================================================================================
    public function recherche_sur(array $colonnes) : static {
        $this->colonnes_recherche = $colonnes;
        $this->cherchable         = true;

        return $this;
    }



    //==================================================================================================================
    // Badge : chemin vers la couleur (ex. niveau_etude.couleur_effective)
    //==================================================================================================================
    public function couleur(string $chemin) : static {
        $this->couleur = $chemin;

        return $this;
    }



    //==================================================================================================================
    // Seconde ligne grise sous la valeur (ex. le CNE sous le nom)
    //==================================================================================================================
    public function sous_texte(string $chemin) : static {
        $this->sous_texte = $chemin;

        return $this;
    }



    //==================================================================================================================
    // Valeur calculée par une fonction (ex. fn($annee) => $annee->active ? 'en_cours' : null)
    //==================================================================================================================
    public function valeur(Closure $valeur) : static {
        $this->valeur = $valeur;

        return $this;
    }



    public function get_valeur_calculee(Model $model) : mixed {
        return $this->valeur ? ($this->valeur)($model) : null;
    }



    public function has_valeur_calculee() : bool {
        return $this->valeur !== null;
    }



    public function get_label() : string {
        return $this->label;
    }



    public function get_nom_colonne() : string {
        return $this->nom_colonne;
    }



    public function get_render() : string {
        return $this->render;
    }



    public function get_colonne_tri() : string {
        return $this->colonne_tri ?? $this->nom_colonne;
    }



    public function get_colonnes_recherche() : array {
        return $this->colonnes_recherche ?: [$this->nom_colonne];
    }



    public function get_couleur() : ?string {
        return $this->couleur;
    }



    public function get_sous_texte() : ?string {
        return $this->sous_texte;
    }



    public function is_triable() : bool {
        return $this->triable;
    }



    public function is_cherchable() : bool {
        return $this->cherchable;
    }



    public function get() : array {
        return [
            'label'       => $this->label,
            'nom_colonne' => $this->nom_colonne,
            'render'      => $this->render,
            'triable'     => $this->triable,
        ];
    }
}
