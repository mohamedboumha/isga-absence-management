<?php

namespace App\Features\Module;

use App\_Core\Base\BaseModel;
use App\Features\Enseignant\Enseignant;
use App\Features\NiveauEtude\NiveauEtude;
use App\Features\Seance\Seance;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $cle
 * @property int $niveau_etude_id
 * @property string $code
 * @property string $intitule
 * @property int $semestre
 * @property int $volume_horaire
 * @property NiveauEtude $niveau_etude
 * @property-read string $semestre_render
 *
 * @method static Builder by_niveau(?NiveauEtude $niveau)
 */
class Module extends BaseModel {
    protected $table = 'modules';

    protected static array $allowed_column_names_as_label = ['intitule', 'code'];



    protected function casts() : array {
        return [
            ...parent::casts(),
            'semestre'       => 'integer',
            'volume_horaire' => 'integer',
        ];
    }



    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    //
    // RELATIONS
    //
    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    public function niveau_etude() : BelongsTo {
        return $this->belongsTo(NiveauEtude::class, 'niveau_etude_id');
    }



    public function enseignants() : BelongsToMany {
        return $this->belongsToMany(Enseignant::class, 'enseignant_module', 'module_id', 'enseignant_id');
    }



    public function seances() : HasMany {
        return $this->hasMany(Seance::class, 'module_id');
    }



    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    //
    // ATTRIBUTES
    //
    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    protected function semestreRender() : Attribute {
        return Attribute::get(fn() => "Semestre {$this->semestre}");
    }



    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    //
    // BUSINESS
    //
    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    public static function faker(array $params) : array {
        /** @var NiveauEtude $niveau */
        $niveau = $params['niveau'];

        $intitules = [
            "Algorithmique", "Bases de données", "Développement web", "Réseaux informatiques",
            "Systèmes d'exploitation", "Programmation orientée objet", "Mathématiques", "Statistiques",
            "Anglais", "Communication", "Gestion de projet", "Droit",
        ];

        //==============================================================================================================
        // Code libre : 3CI-IABD-01, 3CI-IABD-02...
        //==============================================================================================================
        $codes_pris = static::withTrashed()
                            ->pluck('code')
                            ->all();

        $code = collect(range(1, 99))
            ->map(fn(int $numero) => sprintf('%s-%02d', $niveau->code, $numero))
            ->first(fn(string $code) => !in_array($code, $codes_pris, true));

        return [
            'niveau_etude_id' => $niveau->id,
            'code'            => $code,
            'intitule'        => $params['intitule'] ?? fake()->randomElement($intitules),
            'semestre'        => $params['semestre'] ?? fake()->numberBetween(1, $niveau->nb_semestres),
            'volume_horaire'  => fake()->randomElement([24, 30, 36, 42, 48]),
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
    public function scopeBy_niveau(Builder $query, ?NiveauEtude $niveau) : Builder {
        return $query->when($niveau, fn(Builder $query) => $query->where('niveau_etude_id', $niveau->id));
    }
}
