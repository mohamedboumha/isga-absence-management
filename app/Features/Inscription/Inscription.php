<?php

namespace App\Features\Inscription;

use App\_Core\Base\BaseModel;
use App\Features\AnneeUniversitaire\AnneeUniversitaire;
use App\Features\Etudiant\Etudiant;
use App\Features\Groupe\Groupe;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $cle
 * @property int $etudiant_id
 * @property int $groupe_id
 * @property int $annee_universitaire_id
 * @property string|null $decision
 * @property Etudiant $etudiant
 * @property Groupe $groupe
 * @property AnneeUniversitaire $annee_universitaire
 * @property-read string|null $decision_render
 */
class Inscription extends BaseModel {
    protected $table = 'inscriptions';


    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    //
    // RELATIONS
    //
    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    public function etudiant() : BelongsTo {
        return $this->belongsTo(Etudiant::class, 'etudiant_id');
    }



    public function groupe() : BelongsTo {
        return $this->belongsTo(Groupe::class, 'groupe_id');
    }



    public function annee_universitaire() : BelongsTo {
        return $this->belongsTo(AnneeUniversitaire::class, 'annee_universitaire_id');
    }



    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    //
    // ATTRIBUTES
    //
    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    protected function decisionRender() : Attribute {
        return Attribute::get(fn() => $this->decision ? (InscriptionService::decisions[$this->decision] ?? $this->decision) : null);
    }



    public function get_label() : string {
        return "{$this->etudiant?->nom_complet} — {$this->groupe?->nom}";
    }
}
