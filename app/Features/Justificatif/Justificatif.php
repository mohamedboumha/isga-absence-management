<?php

namespace App\Features\Justificatif;

use App\_Core\Base\BaseModel;
use App\Features\Absence\Absence;
use App\Features\Etudiant\Etudiant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $cle
 * @property int $etudiant_id
 * @property string $type
 * @property \Carbon\Carbon|null $date_debut
 * @property \Carbon\Carbon|null $date_fin
 * @property string|null $motif
 * @property \Carbon\Carbon|null $date_depot
 * @property string|null $fichier_chemin
 * @property string|null $fichier_nom
 * @property string $statut
 * @property string|null $motif_refus
 * @property int|null $traite_par
 * @property \Carbon\Carbon|null $traite_le
 * @property Etudiant $etudiant
 * @property User|null $traite_par_user
 * @property-read string $periode_render
 * @property-read string $statut_render
 * @property-read string $type_render
 * @property-read bool $hors_delai
 */
class Justificatif extends BaseModel {
    protected $table = 'justificatifs';

    protected $hidden = ['fichier_chemin']; // le chemin interne n'est jamais envoyé au navigateur



    protected function casts() : array {
        return [
            ...parent::casts(),
            'date_debut' => 'date:Y-m-d',
            'date_fin'   => 'date:Y-m-d',
            'date_depot' => 'date:Y-m-d',
            'traite_le'  => 'datetime',
        ];
    }



    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    //
    // RELATIONS
    //
    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    public function etudiant() : BelongsTo {
        return $this->belongsTo(Etudiant::class, 'etudiant_id');
    }



    public function absences() : HasMany {
        return $this->hasMany(Absence::class, 'justificatif_id');
    }



    public function traite_par_user() : BelongsTo {
        return $this->belongsTo(User::class, 'traite_par');
    }



    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    //
    // ATTRIBUTES
    //
    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    protected function periodeRender() : Attribute {
        return Attribute::get(fn() => $this->date_debut?->format('d/m/Y') . ' → ' . $this->date_fin?->format('d/m/Y'));
    }



    protected function statutRender() : Attribute {
        return Attribute::get(fn() => JustificatifService::statuts[$this->statut] ?? $this->statut);
    }



    protected function typeRender() : Attribute {
        return Attribute::get(fn() => JustificatifService::types[$this->type] ?? $this->type);
    }



    protected function horsDelai() : Attribute {
        //==============================================================================================================
        // RG-07 : déposé plus de N jours après la fin de la période justifiée
        //==============================================================================================================
        return Attribute::get(function () {
            if (!$this->date_depot || !$this->date_fin) {
                return false;
            }

            return $this->date_depot->isAfter($this->date_fin->copy()
                                                             ->addDays(JustificatifService::delai_depot_jours));
        });
    }



    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    //
    // BUSINESS
    //
    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    //==================================================================================================================
    // Absences de l'étudiant dont la séance tombe dans la période du justificatif
    //==================================================================================================================
    public function get_absences_couvertes() : Collection {
        return Absence
            ::query()
            ->where('etudiant_id', $this->etudiant_id)
            ->whereHas('seance', fn(Builder $query) => $query->whereBetween('date', [
                $this->date_debut?->format('Y-m-d'),
                $this->date_fin?->format('Y-m-d'),
            ]))
            ->with('seance.module')
            ->get();
    }



    public function is_en_attente() : bool {
        return $this->statut === JustificatifService::statut_en_attente;
    }



    public function can_be_updated() : bool {
        return $this->is_en_attente(); // un justificatif traité est figé
    }



    public function can_be_deleted() : bool {
        return $this->is_en_attente(); // on garde la trace des justificatifs traités
    }
}
