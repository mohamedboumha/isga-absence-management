<?php

namespace App\Features\AnneeUniversitaire;

use App\_Core\Base\BaseModel;
use App\Features\Groupe\Groupe;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $cle
 * @property string $libelle
 * @property \Carbon\Carbon $date_debut
 * @property \Carbon\Carbon $date_fin
 * @property bool $active
 *
 * @method static Builder active()
 * @method static Builder ordre_recent()
 */
class AnneeUniversitaire extends BaseModel {
    protected $table = 'annees_universitaires';



    protected function casts() : array {
        return [
            ...parent::casts(),
            'date_debut' => 'date:Y-m-d',
            'date_fin'   => 'date:Y-m-d',
            'active'     => 'boolean',
        ];
    }



    protected static function booted() : void {
        parent::booted();

        //==============================================================================================================
        // Une seule année active : si celle-ci devient active, on désactive les autres
        //==============================================================================================================
        static::saving(static function (AnneeUniversitaire $annee) {
            if (!$annee->active) {
                return;
            }

            static
                ::query()
                ->when($annee->exists, fn($query) => $query->whereKeyNot($annee->id))
                ->where('active', true)
                ->update(['active' => false]);
        });
    }



    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    //
    // RELATIONS
    //
    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    public function groupes() : HasMany {
        return $this->hasMany(Groupe::class, 'annee_universitaire_id');
    }


    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    //
    // ATTRIBUTES
    //
    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    protected function periodeRender() : Attribute {
        //==============================================================================================================
        // $annee->periode_render  =>  "01/09/2026 → 31/07/2027"
        //==============================================================================================================
        return Attribute::get(
            fn() => $this->date_debut?->format('d/m/Y') . ' → ' . $this->date_fin?->format('d/m/Y')
        );
    }



    public function can_be_deleted() : bool {
        //==============================================================================================================
        // Interdit si l'année est active ou contient des groupes
        //==============================================================================================================
        return !$this->active && !$this->groupes()
                                       ->exists();
    }


    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    //
    // SCOPES
    //
    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    public function scopeActive(Builder $query) : Builder {
        return $query->where('active', true);
    }



    public function scopeOrdre_recent(Builder $query) : Builder {
        return $query->orderByDesc('date_debut');
    }



    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    //
    // BUSINESS
    //
    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    public static function faker(array $params = []) : array {
        //==============================================================================================================
        // Choisir une année de début libre (en comptant aussi les années supprimées)
        //==============================================================================================================
        $annees_existantes = static
            ::withTrashed()
            ->pluck('libelle')
            ->map(fn(string $libelle) => (int) substr($libelle, 0, 4))
            ->all();

        $annee_debut = fake()->randomElement(array_diff(range(2000, 2050), $annees_existantes));

        return [
            'libelle'    => $annee_debut . '-' . ($annee_debut + 1),
            'date_debut' => "$annee_debut-09-01",
            'date_fin'   => ($annee_debut + 1) . "-07-31",
            'active'     => $params['active'] ?? false,
        ];
    }
}
