<?php

namespace App\Features\Absence;

use App\_Core\Base\BaseModel;
use App\Features\Etudiant\Etudiant;
use App\Features\Seance\Seance;
use App\Models\User;
use App\Features\Justificatif\Justificatif;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $cle
 * @property int $seance_id
 * @property int $etudiant_id
 * @property int|null $saisie_par
 * @property bool $justifiee
 * @property string|null $remarque
 * @property Seance $seance
 * @property Etudiant $etudiant
 * @property User|null $saisi_par_user
 * @property int|null $justificatif_id
 * @property Justificatif|null $justificatif
 */
class Absence extends BaseModel {
    protected $table = 'absences';



    protected function casts() : array {
        return [
            ...parent::casts(),
            'justifiee' => 'boolean',
        ];
    }



    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    //
    // RELATIONS
    //
    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    public function seance() : BelongsTo {
        return $this->belongsTo(Seance::class, 'seance_id');
    }



    public function etudiant() : BelongsTo {
        return $this->belongsTo(Etudiant::class, 'etudiant_id');
    }



    public function justificatif() : BelongsTo {
        return $this->belongsTo(Justificatif::class, 'justificatif_id');
    }



    public function saisi_par_user() : BelongsTo {
        return $this->belongsTo(User::class, 'saisie_par');
    }
}
