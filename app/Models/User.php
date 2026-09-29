<?php

namespace App\Models;

use App\Features\User\UserService;
use Database\Factories\UserFactory;
use App\Features\Enseignant\Enseignant;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

class User extends Authenticatable {
    use HasFactory, Notifiable;

    protected function casts() : array {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }



    public function enseignant() : HasOne {
        return $this->hasOne(Enseignant::class, 'user_id');
    }



    public function is_super_admin() : bool {
        return $this->role === UserService::role_super_admin;
    }
}
