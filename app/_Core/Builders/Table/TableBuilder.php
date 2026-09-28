<?php

namespace App\_Core\Builders\Table;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TableBuilder {
    const int default_per_page = 15;
    const int max_per_page     = 100;

    protected Builder  $query;
    protected array    $columns       = [];
    protected ?string  $search        = null;
    protected ?string  $tri_par       = null;
    protected string   $tri_direction = 'asc';
    protected int      $per_page      = self::default_per_page;
    protected ?Closure $row_url       = null;



    public static function new(Builder $query) : static {
        $table_builder = new static();

        $table_builder->query = $query;


        //==============================================================================================================
        // Paramètres envoyés par le tableau Vue (?search=...&tri_par=...&tri_direction=...&per_page=...)
        //==============================================================================================================
        $table_builder->search        = request('search');
        $table_builder->tri_par       = request('tri_par');
        $table_builder->tri_direction = request('tri_direction') === 'desc' ? 'desc' : 'asc';
        $table_builder->per_page      = min(max((int) request('per_page', self::default_per_page), 1), self::max_per_page);

        return $table_builder;
    }



    public function add_column(TableColumn $column) : static {
        $this->columns[] = $column;

        return $this;
    }



    public function default_tri(string $nom_colonne, string $direction = 'asc') : static {
        //==============================================================================================================
        // Appliqué seulement si l'utilisateur n'a pas choisi de tri
        //==============================================================================================================
        if (!$this->tri_par) {
            $this->tri_par       = $nom_colonne;
            $this->tri_direction = $direction;
        }

        return $this;
    }



    public function row_url(Closure $row_url) : static {
        $this->row_url = $row_url;

        return $this;
    }



    public function get() : array {
        $this->apply_search();
        $this->apply_tri();


        //==============================================================================================================
        // Pagination
        //==============================================================================================================
        $paginator = $this->query
            ->paginate($this->per_page)
            ->withQueryString();


        //==============================================================================================================
        // Lignes : valeurs des colonnes + URL au clic
        //==============================================================================================================
        $items = collect($paginator->items())->map(fn(Model $model) => [
            'cle'     => $model->cle,
            'valeurs' => $this->get_valeurs($model),
            'url'     => $this->row_url ? ($this->row_url)($model) : null,
        ]);

        return [
            'headers'       => array_map(fn(TableColumn $column) => $column->get(), $this->columns),
            'items'         => $items,
            'search'        => $this->search,
            'tri_par'       => $this->tri_par,
            'tri_direction' => $this->tri_direction,
            'pagination'    => [
                'page'      => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total'     => $paginator->total(),
                'per_page'  => $paginator->perPage(),
            ],
        ];
    }



    protected function apply_search() : void {
        //==============================================================================================================
        // Recherche "LIKE %texte%" sur les colonnes cherchables uniquement
        //==============================================================================================================
        $colonnes = array_filter($this->columns, fn(TableColumn $column) => $column->is_cherchable());

        if (!$this->search || !$colonnes) {
            return;
        }

        $this->query->where(function (Builder $query) use ($colonnes) {
            foreach ($colonnes as $column) {
                $query->orWhere($column->get_nom_colonne(), 'like', "%{$this->search}%");
            }
        });
    }



    protected function apply_tri() : void {
        //==============================================================================================================
        // On trie uniquement sur une colonne déclarée triable (jamais un nom de colonne libre venant de l'URL)
        //==============================================================================================================
        $colonnes_triables = array_map(
            fn(TableColumn $column) => $column->get_nom_colonne(),
            array_filter($this->columns, fn(TableColumn $column) => $column->is_triable())
        );

        if ($this->tri_par && in_array($this->tri_par, $colonnes_triables, true)) {
            $this->query->orderBy($this->tri_par, $this->tri_direction);
        }
    }



    protected function get_valeurs(Model $model) : array {
        //==============================================================================================================
        // toArray() applique les casts (dates "Y-m-d", booléens) ; sinon on lit l'attribut (ex. periode_render)
        //==============================================================================================================
        $array   = $model->toArray();
        $valeurs = [];

        foreach ($this->columns as $column) {
            $nom_colonne = $column->get_nom_colonne();

            $valeurs[$nom_colonne] = array_key_exists($nom_colonne, $array)
                ? $array[$nom_colonne]
                : data_get($model, $nom_colonne);
        }

        return $valeurs;
    }
}
