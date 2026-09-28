<?php

namespace App\_Core\Builders\SideBar;

use App\Models\User;

class SideBarBuilder {
    protected array $groupes = [];



    public static function new() : static {
        return new static();
    }



    public function add_groupe(SideBarGroupe $groupe) : static {
        $this->groupes[] = $groupe;

        return $this;
    }



    public function get(?User $user) : array {
        //==============================================================================================================
        // Pas d'utilisateur connecté (pages de login) : pas de sidebar
        //==============================================================================================================
        if (!$user) {
            return [];
        }


        //==============================================================================================================
        // Uniquement les groupes visibles pour ce rôle
        //==============================================================================================================
        return array_values(array_filter(
                                array_map(fn(SideBarGroupe $groupe) => $groupe->get($user), $this->groupes)
                            ));
    }
}
