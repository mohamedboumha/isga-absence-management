<?php

namespace App\_Core\Builders\SideBar;

use App\Models\User;

class SideBarGroupe {
    protected ?string $label = null;
    protected ?array  $roles = null; // null = tous les rôles
    protected array   $items = [];



    public static function new() : static {
        return new static();
    }



    public function label(?string $label) : static {
        $this->label = $label;

        return $this;
    }



    public function roles(array $roles) : static {
        $this->roles = $roles;

        return $this;
    }



    public function add_item(SideBarItem $item) : static {
        $this->items[] = $item;

        return $this;
    }



    public function get(User $user) : ?array {
        //==============================================================================================================
        // Groupe interdit pour ce rôle
        //==============================================================================================================
        if ($this->roles !== null && !in_array($user->role, $this->roles, true)) {
            return null;
        }


        //==============================================================================================================
        // Items visibles ; un groupe sans item visible n'est pas affiché
        //==============================================================================================================
        $items = array_values(array_map(
                                  fn(SideBarItem $item) => $item->get(),
                                  array_filter($this->items, fn(SideBarItem $item) => $item->is_visible($user))
                              ));

        if (!$items) {
            return null;
        }

        return [
            'label' => $this->label,
            'items' => $items,
        ];
    }
}
