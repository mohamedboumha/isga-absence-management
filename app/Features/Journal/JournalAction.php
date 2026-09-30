<?php

namespace App\Features\Journal;

use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $cle
 * @property int|null $user_id
 * @property string $action
 * @property string $entite
 * @property int|null $entite_id
 * @property string|null $entite_cle
 * @property string|null $entite_label
 * @property array|null $anciennes_valeurs
 * @property array|null $nouvelles_valeurs
 * @property string|null $ip
 * @property \Carbon\Carbon|null $created_at
 * @property User|null $user
 * @property-read string $action_render
 * @property-read string $entite_render
 * @property-read string $date_render
 * @property-read string $auteur_render
 */
class JournalAction extends Model {
    //==================================================================================================================
    // Modèle Eloquent simple (pas BaseModel) : le journal ne se journalise pas lui-même
    //==================================================================================================================
    protected $table = 'journal_actions';

    public $timestamps = false;



    protected function casts() : array {
        return [
            'anciennes_valeurs' => 'array',
            'nouvelles_valeurs' => 'array',
            'created_at'        => 'datetime:d/m/Y H:i:s',
        ];
    }



    protected static function booted() : void {
        static::creating(function (JournalAction $journal) {
            $journal->cle        = $journal->cle ?: Str::random(32);
            $journal->created_at = $journal->created_at ?: now();
        });

        //==============================================================================================================
        // Journal immuable : aucune modification ni suppression possible
        //==============================================================================================================
        static::updating(fn() => false);
        static::deleting(fn() => false);
    }



    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    //
    // RELATIONS
    //
    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    public function user() : BelongsTo {
        return $this->belongsTo(User::class, 'user_id');
    }



    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    //
    // ATTRIBUTES
    //
    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    protected function actionRender() : Attribute {
        return Attribute::get(fn() => JournalService::actions[$this->action] ?? $this->action);
    }



    protected function entiteRender() : Attribute {
        return Attribute::get(fn() => JournalService::entites[$this->entite] ?? $this->entite);
    }



    protected function dateRender() : Attribute {
        return Attribute::get(fn() => $this->created_at?->format('d/m/Y H:i:s') ?? '');
    }



    protected function auteurRender() : Attribute {
        return Attribute::get(fn() => $this->user ? trim("{$this->user->prenom} " . mb_strtoupper($this->user->name)) : "Système");
    }
}
