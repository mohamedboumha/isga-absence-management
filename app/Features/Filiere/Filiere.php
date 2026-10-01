<?php

namespace App\Features\Filiere;

use App\_Core\Base\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Features\Cycle\Cycle;
use App\Features\NiveauEtude\NiveauEtude;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $cle
 * @property string $code
 * @property string $nom
 * @property string|null $description
 * @property int $cycle_id
 * @property Cycle $cycle
 */
class Filiere extends BaseModel {
    protected $table = 'filieres';

    protected static array $allowed_column_names_as_label = ['nom', 'code'];


    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    //
    // RELATIONS
    //
    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    public function cycle() : BelongsTo {
        return $this->belongsTo(Cycle::class, 'cycle_id');
    }



    public function niveaux() : HasMany {
        return $this->hasMany(NiveauEtude::class, 'filiere_id');
    }



    public function can_be_deleted() : bool {
        return !$this->niveaux()->exists();
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
            'cycle_id'    => ($params['cycle'] ?? Cycle::query()->firstOrFail())->id,
        ];
    }
}
