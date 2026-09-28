<?php

namespace App\_Core\Builders\SideBar;

use App\Models\User;
use Illuminate\Support\Facades\Route;

class SideBarItem {
    protected string $label        = '';
    protected string $icon         = 'Circle';
    protected string $route        = '';
    protected array  $route_params = [];
    protected ?array $roles        = null;



    public static function new() : static {
        return new static();
    }



    public function label(string $label) : static {
        $this->label = $label;

        return $this;
    }



    public function icon(string $icon) : static {
        $this->icon = $icon;

        return $this;
    }



    public function route(string $route, array $route_params = []) : static {
        $this->route        = $route;
        $this->route_params = $route_params;

        return $this;
    }



    public function roles(array $roles) : static {
        $this->roles = $roles;

        return $this;
    }



    public function is_visible(User $user) : bool {
        //==============================================================================================================
        // Invisible si la route n'existe pas encore (feature pas encore développée)
        //==============================================================================================================
        if (!Route::has($this->route)) {
            return false;
        }


        //==============================================================================================================
        // Invisible si le rôle de l'utilisateur n'est pas autorisé
        //==============================================================================================================
        return $this->roles === null || in_array($user->role, $this->roles, true);
    }



    public function get() : array {
        return [
            'label' => $this->label,
            'icon'  => $this->icon,
            'href'  => route($this->route, $this->route_params),
        ];
    }
}
