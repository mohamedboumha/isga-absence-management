<?php

namespace App\Features\Seance;

use App\_Core\Base\BaseModel;
use App\Features\Absence\Absence;
use App\Features\Enseignant\Enseignant;
use App\Features\Groupe\Groupe;
use App\Features\Module\Module;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $cle
 * @property int $module_id
 * @property int $enseignant_id
 * @property int $groupe_id
 * @property \Carbon\Carbon|null $date
 * @property string|null $heure_debut
 * @property string|null $heure_fin
 * @property string $type
 * @property string|null $salle
 * @property bool $annulee
 * @property \Carbon\Carbon|null $appel_fait_le
 * @property int|null $appel_fait_par
 * @property Module $module
 * @property Enseignant $enseignant
 * @property Groupe $groupe
 * @property User|null $appel_fait_par_user
 * @property-read string $horaire_render
 * @property-read string $statut_render
 * @property-read string $statut_cle
 * @property-read float $duree_heures
 * @property-read bool $appel_fait
 *
 * @method static Builder non_annulees()
 */
class Seance extends BaseModel {
    protected $table = 'seances';



    protected function casts() : array {
        return [
            ...parent::casts(),
            'date'          => 'date:Y-m-d',
            'annulee'       => 'boolean',
            'appel_fait_le' => 'datetime',
        ];
    }



    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    //
    // RELATIONS
    //
    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    public function module() : BelongsTo {
        return $this->belongsTo(Module::class, 'module_id');
    }



    public function enseignant() : BelongsTo {
        return $this->belongsTo(Enseignant::class, 'enseignant_id');
    }



    public function groupe() : BelongsTo {
        return $this->belongsTo(Groupe::class, 'groupe_id');
    }



    public function absences() : HasMany {
        return $this->hasMany(Absence::class, 'seance_id');
    }



    public function appel_fait_par_user() : BelongsTo {
        return $this->belongsTo(User::class, 'appel_fait_par');
    }



    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    //
    // ATTRIBUTES
    //
    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    protected function heureDebut() : Attribute {
        //==============================================================================================================
        // MySQL renvoie "08:30:00" ; on expose "08:30"
        //==============================================================================================================
        return Attribute::get(fn(?string $valeur) => $valeur ? substr($valeur, 0, 5) : null);
    }



    protected function heureFin() : Attribute {
        return Attribute::get(fn(?string $valeur) => $valeur ? substr($valeur, 0, 5) : null);
    }



    protected function horaireRender() : Attribute {
        //==============================================================================================================
        // $seance->horaire_render  =>  "08:30 – 10:30"
        //==============================================================================================================
        return Attribute::get(fn() => "{$this->heure_debut} – {$this->heure_fin}");
    }



    protected function statutRender() : Attribute {
        return Attribute::get(fn() => $this->annulee ? "Annulée" : "Planifiée");
    }



    protected function statutCle() : Attribute {
        //==============================================================================================================
        // Clé de la pastille (statuts.ts) : annulée, appel fait, appel à faire (jour J ou passé), planifiée (à venir)
        //==============================================================================================================
        return Attribute::get(function () {
            if ($this->annulee) {
                return 'annulee';
            }

            if ($this->appel_fait_le) {
                return 'appel_fait';
            }

            return $this->date && $this->date->lte(today()) ? 'appel_a_faire' : 'planifiee';
        });
    }



    protected function dureeHeures() : Attribute {
        //==============================================================================================================
        // Durée en heures (pour les taux d'absence, RG-08) : "08:30" → "10:30" = 2.0
        //==============================================================================================================
        return Attribute::get(function () {
            if (!$this->heure_debut || !$this->heure_fin) {
                return 0.0;
            }

            [$h1, $m1] = array_map('intval', explode(':', $this->heure_debut));
            [$h2, $m2] = array_map('intval', explode(':', $this->heure_fin));

            return round((($h2 * 60 + $m2) - ($h1 * 60 + $m1)) / 60, 2);
        });
    }



    protected function appelFait() : Attribute {
        return Attribute::get(fn() => $this->appel_fait_le !== null);
    }



    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    //
    // BUSINESS
    //
    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    public static function faker(array $params) : array {
        /** @var Groupe $groupe */
        $groupe = $params['groupe'];

        /** @var Module $module */
        $module = $params['module'];

        /** @var Enseignant $enseignant */
        $enseignant = $params['enseignant'];

        [$heure_debut, $heure_fin] = fake()->randomElement(SeanceService::creneaux);

        return [
            'module_id'     => $module->id,
            'enseignant_id' => $enseignant->id,
            'groupe_id'     => $groupe->id,
            'date'          => $params['date'] ?? now()->format('Y-m-d'),
            'heure_debut'   => $heure_debut,
            'heure_fin'     => $heure_fin,
            'type'          => fake()->randomElement(SeanceService::types),
            'salle'         => fake()->randomElement(['A', 'B', 'C']) . fake()->numberBetween(101, 320),
            'annulee'       => false,
        ];
    }



    public function can_be_deleted() : bool {
        //==============================================================================================================
        // Une séance dont l'appel est fait ne peut plus être supprimée (elle peut être annulée)
        //==============================================================================================================
        return $this->appel_fait_le === null;
    }



    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    //
    // SCOPES
    //
    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    public function scopeNon_annulees(Builder $query) : Builder {
        return $query->where('annulee', false);
    }
}
