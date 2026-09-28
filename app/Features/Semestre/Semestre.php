<?php

namespace App\Features\Semestre;

use App\_Core\Base\BaseModel;
use App\Features\AnneeUniversitaire\AnneeUniversitaire;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $cle
 * @property int $annee_universitaire_id
 * @property string $libelle
 * @property \Carbon\Carbon $date_debut
 * @property \Carbon\Carbon $date_fin
 * @property AnneeUniversitaire $annee_universitaire
 *
 * @method static Builder by_annee(?AnneeUniversitaire $annee)
 */
class Semestre extends BaseModel {
    protected $table = 'semestres';



    protected function casts() : array {
        return [
            ...parent::casts(),
            'date_debut' => 'date:Y-m-d',
            'date_fin'   => 'date:Y-m-d',
        ];
    }



    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    //
    // RELATIONS
    //
    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    public function annee_universitaire() : BelongsTo {
        return $this->belongsTo(AnneeUniversitaire::class, 'annee_universitaire_id');
    }



    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    //
    // ATTRIBUTES
    //
    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    protected function periodeRender() : Attribute {
        return Attribute::get(
            fn() => $this->date_debut?->format('d/m/Y') . ' → ' . $this->date_fin?->format('d/m/Y')
        );
    }



    public function can_be_deleted() : bool {
        // TODO : interdire si le semestre contient des modules / séances
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



    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    //
    // BUSINESS
    //
    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    public static function faker(array $params) : array {
        //==============================================================================================================
        // Un libellé libre (S1...S10) dans l'année donnée
        //==============================================================================================================
        /** @var AnneeUniversitaire $annee */
        $annee = $params['annee'];

        $libelles_pris = static
            ::withTrashed()
            ->where('annee_universitaire_id', $annee->id)
            ->pluck('libelle')
            ->all();

        $libelle = fake()->randomElement(array_diff(array_map(fn($i) => "S$i", range(1, 10)), $libelles_pris));


        //==============================================================================================================
        // Semestre impair = 1er semestre de l'année, pair = 2e semestre
        //==============================================================================================================
        $est_premier = ((int) substr($libelle, 1)) % 2 === 1;

        return [
            'annee_universitaire_id' => $annee->id,
            'libelle'                => $libelle,
            'date_debut'             => $est_premier ? $annee->date_debut->format('Y-m-d') : $annee->date_debut->copy()
                                                                                                               ->addMonths(5)
                                                                                                               ->format('Y-m-d'),
            'date_fin'               => $est_premier ? $annee->date_debut->copy()
                                                                         ->addMonths(4)
                                                                         ->format('Y-m-d') : $annee->date_fin->format('Y-m-d'),
        ];
    }
}
