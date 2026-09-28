<?php

namespace App\_Core\Builders\Table;

use App\_Core\Services\RendersService;

class TableColumn {
    protected string $label       = '';
    protected string $nom_colonne = '';
    protected string $render      = RendersService::render_chaine;
    protected bool   $triable     = false;
    protected bool   $cherchable  = false;



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



    public function get_nom_colonne() : string {
        return $this->nom_colonne;
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
