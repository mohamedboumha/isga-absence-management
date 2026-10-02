<?php

namespace App\Features\Filiere;

use App\_Core\Base\BaseModel;
use App\_Core\Services\CouleurService;
use App\Features\Cycle\Cycle;
use App\Features\NiveauEtude\NiveauEtude;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $cle
 * @property int $cycle_id
 * @property string $code
 * @property string $nom
 * @property string|null $couleur
 * @property string|null $description
 * @property Cycle $cycle
 * @property-read string $couleur_effective
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



    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    //
    // ATTRIBUTES
    //
    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    protected function couleurEffective() : Attribute {
        //==============================================================================================================
        // Sa couleur, sinon celle de son cycle
        //==============================================================================================================
        return Attribute::get(fn() => $this->couleur ?? $this->cycle?->couleur ?? CouleurService::couleur_defaut);
    }



    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    //
    // BUSINESS
    //
    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    public static function faker(array $params = []) : array {
        //==============================================================================================================
        // Une filière pas encore créée (en comptant les supprimées)
        //==============================================================================================================
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
            'cycle_id'    => ($params['cycle'] ?? Cycle::query()
                                                       ->firstOrFail())->id,
            'code'        => $code,
            'nom'         => $filieres[$code],
            'couleur'     => fake()->randomElement(CouleurService::get_valeurs()),
            'description' => fake('fr_FR')->sentence(12),
        ];
    }



    public function can_be_deleted() : bool {
        return !$this->niveaux()
                     ->exists();
    }
}
