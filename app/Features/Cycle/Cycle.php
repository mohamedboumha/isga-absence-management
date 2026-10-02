<?php

namespace App\Features\Cycle;

use App\_Core\Base\BaseModel;
use App\Features\Filiere\Filiere;
use App\Features\NiveauEtude\NiveauEtude;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $cle
 * @property string $code
 * @property string $nom
 * @property int $nb_annees
 * @property string $couleur
 */
class Cycle extends BaseModel {
    protected $table = 'cycles';

    protected static array $allowed_column_names_as_label = ['nom', 'code'];



    protected function casts() : array {
        return [
            ...parent::casts(),
            'nb_annees' => 'integer',
        ];
    }



    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    //
    // RELATIONS
    //
    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    public function filieres() : HasMany {
        return $this->hasMany(Filiere::class, 'cycle_id');
    }



    public function niveaux() : HasMany {
        return $this->hasMany(NiveauEtude::class, 'cycle_id');
    }



    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    //
    // BUSINESS
    //
    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    public function can_be_deleted() : bool {
        return !$this->filieres()
                     ->exists() && !$this->niveaux()
                                         ->exists();
    }
}
