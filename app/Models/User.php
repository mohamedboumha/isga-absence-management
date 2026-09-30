<?php

namespace App\Models;

use App\Features\User\UserService;
use Database\Factories\UserFactory;
use App\Features\Enseignant\Enseignant;
use App\Features\Journal\JournalObserver;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $cle
 * @property string $name
 * @property string|null $prenom
 * @property string $email
 * @property string $role
 * @property bool $actif
 * @property-read string $role_render
 */
class User extends Authenticatable {
    use HasFactory, Notifiable;

    protected static function booted() : void {
        static::observe(JournalObserver::class);

        //==============================================================================================================
        // Même convention que BaseModel : une clé aléatoire à la création
        //==============================================================================================================
        static::creating(function (User $user) {
            if (empty($user->cle)) {
                $user->cle = Str::random(32);
            }
        });
    }



    protected function roleRender() : Attribute {
        return Attribute::get(fn() => UserService::roles_labels[$this->role] ?? $this->role);
    }



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
