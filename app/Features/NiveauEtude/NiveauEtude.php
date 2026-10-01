<?php

namespace App\Features\NiveauEtude;

use App\_Core\Base\BaseModel;
use App\Features\Cycle\Cycle;
use App\Features\Filiere\Filiere;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Features\Groupe\Groupe;
use App\Features\Module\Module;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $cle
 * @property int $cycle_id
 * @property int|null $filiere_id
 * @property int $annee_cycle
 * @property string $code
 * @property string $libelle
 * @property int $nb_semestres
 * @property Cycle $cycle
 * @property Filiere|null $filiere
 * @property Collection<int, NiveauEtude> $suivants
 * @property Collection<int, NiveauEtude> $precedents
 * @property-read bool $est_derniere_annee
 * @property-read string $suivants_render
 */
class NiveauEtude extends BaseModel {
    protected $table = 'niveaux_etudes';

    protected static array $allowed_column_names_as_label = ['libelle', 'code'];



    protected function casts() : array {
        return [
            ...parent::casts(),
            'annee_cycle'  => 'integer',
            'nb_semestres' => 'integer',
        ];
    }



    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    //
    // RELATIONS
    //
    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    public function cycle() : BelongsTo {
        return $this->belongsTo(Cycle::class, 'cycle_id');
    }



    public function filiere() : BelongsTo {
        return $this->belongsTo(Filiere::class, 'filiere_id');
    }



    //==================================================================================================================
    // Parcours : les niveaux où l'on peut aller l'année suivante...
    //==================================================================================================================
    public function suivants() : BelongsToMany {
        return $this->belongsToMany(NiveauEtude::class, 'parcours', 'niveau_id', 'niveau_suivant_id');
    }



    //==================================================================================================================
    // ...et, dans l'autre sens, ceux qui mènent à ce niveau
    //==================================================================================================================
    public function precedents() : BelongsToMany {
        return $this->belongsToMany(NiveauEtude::class, 'parcours', 'niveau_suivant_id', 'niveau_id');
    }

    public function groupes() : HasMany {
        return $this->hasMany(Groupe::class, 'niveau_etude_id');
    }



    public function modules() : HasMany {
        return $this->hasMany(Module::class, 'niveau_etude_id');
    }


    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    //
    // ATTRIBUTES
    //
    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    protected function estDerniereAnnee() : Attribute {
        return Attribute::get(fn() => $this->annee_cycle >= $this->cycle->nb_annees);
    }



    protected function suivantsRender() : Attribute {
        //==============================================================================================================
        // "ISI-1, ISII-1" ou "Diplôme" en dernière année
        //==============================================================================================================
        return Attribute::get(fn() => $this->est_derniere_annee
            ? "Diplôme"
            : ($this->suivants->pluck('code')
                              ->join(', ') ?: "—"));
    }



    public function get_label() : string {
        return $this->libelle;
    }



    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    //
    // BUSINESS
    //
    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    public function can_be_deleted() : bool {
        return !$this->groupes()->exists() && !$this->modules()->exists();
    }
}
