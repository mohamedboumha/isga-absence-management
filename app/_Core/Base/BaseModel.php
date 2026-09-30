<?php

namespace App\_Core\Base;

use Illuminate\Database\Eloquent\Builder;
use App\Features\Journal\JournalObserver;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

abstract class BaseModel extends Model {
    use SoftDeletes;

    const int length_model_cle = 32;

    protected $guarded = ['id', 'cle'];

    protected static array $allowed_column_names_as_label = ['nom', 'libelle', 'titre', 'label'];



    protected static function booted() : void {
        parent::booted();

        //==============================================================================================================
        // Journal des actions : chaque modèle métier est observé (traçabilité, section 5)
        //==============================================================================================================
        static::observe(JournalObserver::class);

        //==============================================================================================================
        // Génération de la clé à la création
        //==============================================================================================================
        static::creating(static function (BaseModel $model) {
            if (empty($model->cle)) {
                $model->cle = static::generate_model_unique_cle();
            }
        });


        //==============================================================================================================
        // Nouvelle clé en cas de duplication
        //==============================================================================================================
        static::replicating(static function (BaseModel $model) {
            $model->cle = static::generate_model_unique_cle();
        });
    }



    protected function casts() : array {
        return [
            'cle' => 'string',
        ];
    }



    protected static function generate_model_unique_cle() : string {
        do {
            $cle = Str::random(self::length_model_cle);
        } while (static::withTrashed()
                       ->where('cle', $cle)
                       ->exists());

        return $cle;
    }



    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    //
    // RÉCUPÉRATION PAR CLÉ
    //
    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    public static function get_by_cle(?string $cle) : ?static {
        if (!$cle) {
            return null;
        }

        return static
            ::where('cle', $cle)
            ->first();
    }



    public static function get_by_cle_with_trashed(?string $cle) : ?static {
        if (!$cle) {
            return null;
        }

        return static
            ::withTrashed()
            ->where('cle', $cle)
            ->first();
    }



    public static function get_by_cles(?array $cles) : ?Collection {
        if (!$cles) {
            return null;
        }

        return static
            ::whereIn('cle', $cles)
            ->get();
    }



    public static function get_by_cle_or_new(?string $cle) : static {
        return static::get_by_cle($cle) ?? new static();
    }



    public static function update_by_cle_or_create(?string $cle, array $attributes = []) : static {
        //==============================================================================================================
        // Récupérer <item> par <clé> ou nouvelle instance
        //==============================================================================================================
        $model = static::get_by_cle_or_new($cle);


        //==============================================================================================================
        // Remplir et sauvegarder
        //==============================================================================================================
        $model->fill($attributes);
        $model->save();

        return $model;
    }



    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    //
    // LABEL, SELECT, TABLE
    //
    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    public function get_label() : string {
        foreach (static::$allowed_column_names_as_label as $column_name) {
            if (!empty($this->$column_name)) {
                return (string) $this->$column_name;
            }
        }

        return "#{$this->id}";
    }



    public static function get_label_column() : ?string {
        $columns = (new static())
            ->getConnection()
            ->getSchemaBuilder()
            ->getColumnListing(static::get_nom_table());

        foreach (static::$allowed_column_names_as_label as $column_name) {
            if (in_array($column_name, $columns, true)) {
                return $column_name;
            }
        }

        return null;
    }



    public static function list_pour_select(string $valeur_column = 'id', ?string $label_column = null, ?string $order_by = null) : array {
        $label_column = $label_column ?? static::get_label_column();

        return static
            ::query()
            ->selectRaw("$valeur_column as valeur")
            ->selectRaw("$label_column as label")
            ->orderBy($order_by ?? $label_column)
            ->get()
            ->toArray();
    }



    public static function get_nom_table() : string {
        return (new static())->getTable();
    }



    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    //
    // PERMISSIONS MÉTIER (surchargées dans les Business des features)
    //
    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    public function can_be_updated() : bool {
        return true;
    }



    public function can_be_deleted() : bool {
        return true;
    }



    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    //
    // SCOPES
    //
    //[][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][][]
    public function scopeCle(Builder $query, ?string $cle) : Builder {
        return $query->where('cle', $cle);
    }
}
