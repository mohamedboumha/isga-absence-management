<?php

namespace App\Features\Enseignant;

use App\_Core\Base\BaseModel;
use App\Features\Module\Module;
use App\Models\User;
use App\Features\Seance\Seance;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $cle
 * @property int|null $user_id
 * @property string $nom
 * @property string $prenom
 * @property string $email
 * @property string|null $telephone
 * @property User|null $user
 * @property Collection<int, Module> $modules
 * @property-read string $nom_complet
 */
class Enseignant extends BaseModel {
    protected $table = 'enseignants';


    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    //
    // RELATIONS
    //
    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    public function user() : BelongsTo {
        return $this->belongsTo(User::class, 'user_id');
    }



    public function modules() : BelongsToMany {
        return $this->belongsToMany(Module::class, 'enseignant_module', 'enseignant_id', 'module_id');
    }



    public function seances() : HasMany {
        return $this->hasMany(Seance::class, 'enseignant_id');
    }


    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    //
    // ATTRIBUTES
    //
    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    protected function nomComplet() : Attribute {
        //==============================================================================================================
        // $enseignant->nom_complet  =>  "Karim BENNANI"
        //==============================================================================================================
        return Attribute::get(fn() => trim("{$this->prenom} " . mb_strtoupper((string) $this->nom)));
    }



    public function get_label() : string {
        return $this->nom_complet;
    }



    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    //
    // BUSINESS
    //
    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    public static function faker(array $params = []) : array {
        $faker  = fake('fr_FR');
        $prenom = $faker->firstName();
        $nom    = $faker->lastName();

        return [
            'nom'       => $nom,
            'prenom'    => $prenom,
            'email'     => Str::slug("$prenom.$nom", '.') . '.' . $faker->unique()
                                                                        ->numberBetween(10, 999) . '@isga.ma',
            'telephone' => '06' . $faker->numerify('########'),
            'modules'   => $params['modules'] ?? [],
        ];
    }



    public function can_be_deleted() : bool {
        return !$this->seances()
                     ->exists();
    }
}
