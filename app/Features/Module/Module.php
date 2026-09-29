<?php

namespace App\Features\Module;

use App\_Core\Base\BaseModel;
use App\Features\Seance\Seance;
use App\Features\Filiere\Filiere;
use App\Features\Enseignant\Enseignant;
use App\Features\Filiere\FiliereService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $cle
 * @property int $filiere_id
 * @property string $code
 * @property string $intitule
 * @property string $semestre
 * @property int $volume_horaire
 * @property Filiere $filiere
 * @property-read string|null $niveau
 *
 * @method static Builder by_filiere(?Filiere $filiere)
 */
class Module extends BaseModel {
    protected $table = 'modules';

    protected static array $allowed_column_names_as_label = ['intitule', 'code'];



    protected function casts() : array {
        return [
            ...parent::casts(),
            'volume_horaire' => 'integer',
        ];
    }



    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    //
    // RELATIONS
    //
    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    public function filiere() : BelongsTo {
        return $this->belongsTo(Filiere::class, 'filiere_id');
    }



    public function seances() : HasMany {
        return $this->hasMany(Seance::class, 'module_id');
    }



    public function enseignants() : BelongsToMany {
        return $this->belongsToMany(Enseignant::class, 'enseignant_module', 'module_id', 'enseignant_id');
    }

    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    //
    // ATTRIBUTES
    //
    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    protected function niveau() : Attribute {
        //==============================================================================================================
        // $module->niveau  =>  "L3" pour un module de S5
        //==============================================================================================================
        return Attribute::get(fn() => FiliereService::get_niveau_by_semestre($this->semestre));
    }



    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    //
    // BUSINESS
    //
    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    public static function faker(array $params) : array {
        /** @var Filiere $filiere */
        $filiere = $params['filiere'];

        $intitules = [
            'ALGO' => "Algorithmique",
            'BDD'  => "Bases de données",
            'WEB'  => "Développement web",
            'RES'  => "Réseaux informatiques",
            'SYS'  => "Systèmes d'exploitation",
            'POO'  => "Programmation orientée objet",
            'MATH' => "Mathématiques",
            'STAT' => "Statistiques",
            'ANG'  => "Anglais",
            'COM'  => "Communication",
            'GPR'  => "Gestion de projet",
            'DRT'  => "Droit",
        ];


        //==============================================================================================================
        // Un code libre : GI-BDD, GC-MATH...
        //==============================================================================================================
        $codes_pris = static::withTrashed()
                            ->pluck('code')
                            ->all();

        $abreviation = collect(array_keys($intitules))
            ->shuffle()
            ->first(fn(string $abreviation) => !in_array("{$filiere->code}-{$abreviation}", $codes_pris, true));

        return [
            'filiere_id'     => $filiere->id,
            'code'           => "{$filiere->code}-{$abreviation}",
            'intitule'       => $intitules[$abreviation],
            'semestre'       => $params['semestre'] ?? fake()->randomElement(FiliereService::semestres),
            'volume_horaire' => fake()->randomElement([24, 30, 36, 42, 48]),
        ];
    }



    public function can_be_deleted() : bool {
        return !$this->seances()
                     ->exists();
    }



    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    //
    // SCOPES
    //
    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    public function scopeBy_filiere(Builder $query, ?Filiere $filiere) : Builder {
        return $query->when($filiere, fn(Builder $query) => $query->where('filiere_id', $filiere->id));
    }
}
