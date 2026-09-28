<?php

namespace App\Features\Groupe;

use App\_Core\Base\BaseModel;
use App\Features\AnneeUniversitaire\AnneeUniversitaire;
use App\Features\Filiere\Filiere;
use App\Features\Filiere\FiliereService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int                $id
 * @property string             $cle
 * @property int                $annee_universitaire_id
 * @property int                $filiere_id
 * @property string             $niveau
 * @property string             $nom
 * @property AnneeUniversitaire $annee_universitaire
 * @property Filiere            $filiere
 *
 * @method static Builder by_annee(?AnneeUniversitaire $annee)
 * @method static Builder by_filiere(?Filiere $filiere)
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



    public function filiere() : BelongsTo {
        return $this->belongsTo(Filiere::class, 'filiere_id');
    }

    // TODO : etudiants() : HasMany  (feature Etudiant)



    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    //
    // BUSINESS
    //
    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    public static function faker(array $params) : array {
        /** @var AnneeUniversitaire $annee */
        /** @var Filiere $filiere */
        $annee   = $params['annee'];
        $filiere = $params['filiere'];
        $niveau  = $params['niveau'] ?? fake()->randomElement(FiliereService::niveaux);


        //==============================================================================================================
        // Nom libre : GI-L3-A, sinon GI-L3-B, GI-L3-C...
        //==============================================================================================================
        $noms_pris = static
            ::withTrashed()
            ->where('annee_universitaire_id', $annee->id)
            ->pluck('nom')
            ->all();

        $nom = collect(range('A', 'Z'))
            ->map(fn(string $lettre) => "{$filiere->code}-{$niveau}-{$lettre}")
            ->first(fn(string $nom) => !in_array($nom, $noms_pris, true));

        return [
            'annee_universitaire_id' => $annee->id,
            'filiere_id'             => $filiere->id,
            'niveau'                 => $niveau,
            'nom'                    => $nom,
        ];
    }



    public function can_be_deleted() : bool {
        // TODO : interdire si le groupe contient des étudiants ou des séances
        return true;
    }



    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    //
    // SCOPES
    //
    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    public function scopeBy_annee(Builder $query, ?AnneeUniversitaire $annee) : Builder {
        return $query->when($annee, fn(Builder $query) => $query->where('annee_universitaire_id', $annee->id));
    }



    public function scopeBy_filiere(Builder $query, ?Filiere $filiere) : Builder {
        return $query->when($filiere, fn(Builder $query) => $query->where('filiere_id', $filiere->id));
    }
}
