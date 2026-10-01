<?php

namespace App\Features\Groupe;

use App\_Core\Base\BaseModel;
use App\Features\AnneeUniversitaire\AnneeUniversitaire;
use App\Features\Etudiant\Etudiant;
use App\Features\NiveauEtude\NiveauEtude;
use App\Features\Seance\Seance;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Features\Inscription\Inscription;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $cle
 * @property int $annee_universitaire_id
 * @property int $niveau_etude_id
 * @property string $nom
 * @property AnneeUniversitaire $annee_universitaire
 * @property NiveauEtude $niveau_etude
 *
 * @method static Builder by_annee(?AnneeUniversitaire $annee)
 * @method static Builder by_niveau(?NiveauEtude $niveau)
 */
class Groupe extends BaseModel {
    protected $table = 'groupes';


    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    //
    // RELATIONS
    //
    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    public function annee_universitaire() : BelongsTo {
        return $this->belongsTo(AnneeUniversitaire::class, 'annee_universitaire_id');
    }



    public function niveau_etude() : BelongsTo {
        return $this->belongsTo(NiveauEtude::class, 'niveau_etude_id');
    }



    public function inscriptions() : HasMany {
        return $this->hasMany(Inscription::class, 'groupe_id');
    }



    //==================================================================================================================
    // Les étudiants inscrits dans ce groupe (donc pour l'année de ce groupe)
    //==================================================================================================================
    public function etudiants() : BelongsToMany {
        return $this
            ->belongsToMany(Etudiant::class, 'inscriptions', 'groupe_id', 'etudiant_id')
            ->wherePivotNull('deleted_at');
    }



    public function seances() : HasMany {
        return $this->hasMany(Seance::class, 'groupe_id');
    }



    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    //
    // BUSINESS
    //
    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    public static function faker(array $params) : array {
        /** @var AnneeUniversitaire $annee */
        $annee = $params['annee'];

        /** @var NiveauEtude $niveau */
        $niveau = $params['niveau'];


        //==============================================================================================================
        // Nom libre : 3CI-IABD-A, sinon 3CI-IABD-B...
        //==============================================================================================================
        $noms_pris = static::withTrashed()
                           ->where('annee_universitaire_id', $annee->id)
                           ->pluck('nom')
                           ->all();

        $nom = collect(range('A', 'Z'))
            ->map(fn(string $lettre) => "{$niveau->code}-{$lettre}")
            ->first(fn(string $nom) => !in_array($nom, $noms_pris, true));

        return [
            'annee_universitaire_id' => $annee->id,
            'niveau_etude_id'        => $niveau->id,
            'nom'                    => $nom,
        ];
    }



    public function can_be_deleted() : bool {
        return !$this->inscriptions()->exists() && !$this->seances()->exists();
    }


    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    //
    // SCOPES
    //
    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    public function scopeBy_annee(Builder $query, ?AnneeUniversitaire $annee) : Builder {
        return $query->when($annee, fn(Builder $query) => $query->where('annee_universitaire_id', $annee->id));
    }



    public function scopeBy_niveau(Builder $query, ?NiveauEtude $niveau) : Builder {
        return $query->when($niveau, fn(Builder $query) => $query->where('niveau_etude_id', $niveau->id));
    }
}
