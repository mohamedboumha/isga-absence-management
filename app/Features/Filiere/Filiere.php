<?php

namespace App\Features\Filiere;

use App\_Core\Base\BaseModel;
use App\Features\Groupe\Groupe;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $cle
 * @property string $code
 * @property string $nom
 * @property string|null $description
 */
class Filiere extends BaseModel {
    protected $table = 'filieres';

    protected static array $allowed_column_names_as_label = ['nom', 'code'];


    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    //
    // RELATIONS
    //
    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    public function groupes() : HasMany {
        return $this->hasMany(Groupe::class, 'filiere_id');
    }



    public function can_be_deleted() : bool {
        return !$this->groupes()
                     ->exists();
    }


    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    //
    // BUSINESS
    //
    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    public static function faker(array $params = []) : array {
        $filieres = [
            'GI'  => "Génie Informatique",
            'GE'  => "Génie Électrique",
            'GC'  => "Génie Civil",
            'FC'  => "Finance et Comptabilité",
            'MKT' => "Marketing et Communication",
            'RH'  => "Ressources Humaines",
            'LOG' => "Logistique et Transport",
            'IA'  => "Intelligence Artificielle",
            'CYB' => "Cybersécurité",
            'MGT' => "Management des Entreprises",
        ];

        $codes_pris = static::withTrashed()
                            ->pluck('code')
                            ->all();
        $code       = fake()->randomElement(array_diff(array_keys($filieres), $codes_pris));

        return [
            'code'        => $code,
            'nom'         => $filieres[$code],
            'description' => fake('fr_FR')->sentence(12),
        ];
    }
}
