<?php

namespace App\Features\Etudiant;

use App\_Core\Base\BaseModel;
use App\Features\Groupe\Groupe;
use App\Features\Absence\Absence;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $cle
 * @property int $groupe_id
 * @property string $cne
 * @property string $nom
 * @property string $prenom
 * @property string|null $email
 * @property string|null $telephone
 * @property \Carbon\Carbon|null $date_naissance
 * @property Groupe $groupe
 * @property-read string $nom_complet
 *
 * @method static Builder by_groupe(?Groupe $groupe)
 */
class Etudiant extends BaseModel {
    protected $table = 'etudiants';



    protected function casts() : array {
        return [
            ...parent::casts(),
            'date_naissance' => 'date:Y-m-d',
        ];
    }



    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    //
    // RELATIONS
    //
    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    public function groupe() : BelongsTo {
        return $this->belongsTo(Groupe::class, 'groupe_id');
    }



    public function absences() : HasMany {
        return $this->hasMany(Absence::class, 'etudiant_id');
    }


    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    //
    // ATTRIBUTES
    //
    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    protected function nomComplet() : Attribute {
        //==============================================================================================================
        // $etudiant->nom_complet  =>  "Salma EL IDRISSI"
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
    public static function faker(array $params) : array {
        /** @var Groupe $groupe */
        $groupe = $params['groupe'];

        $faker = fake('fr_FR');


        //==============================================================================================================
        // CNE libre : une lettre + 9 chiffres
        //==============================================================================================================
        do {
            $cne = strtoupper($faker->randomLetter()) . $faker->numerify('#########');
        } while (static::withTrashed()
                       ->where('cne', $cne)
                       ->exists());

        return [
            'groupe_id'      => $groupe->id,
            'cne'            => $cne,
            'nom'            => $faker->lastName(),
            'prenom'         => $faker->firstName(),
            'email'          => strtolower($cne) . '@etu.isga.ma',
            'telephone'      => '06' . $faker->numerify('########'),
            'date_naissance' => $faker->dateTimeBetween('-26 years', '-18 years')
                                      ->format('Y-m-d'),
        ];
    }



    public function can_be_deleted() : bool {
        return !$this->absences()
                     ->exists();
    }


    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    //
    // SCOPES
    //
    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    public function scopeBy_groupe(Builder $query, ?Groupe $groupe) : Builder {
        return $query->when($groupe, fn(Builder $query) => $query->where('groupe_id', $groupe->id));
    }
}
