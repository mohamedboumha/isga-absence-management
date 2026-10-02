<?php

namespace App\_Core\Builders\Table;

use App\_Core\Exports\TableauExport;
use App\_Core\Services\RendersService;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TableBuilder {
    const int   default_per_page  = 15;
    const int   max_per_page      = 100;
    const int   max_lignes_export = 10000;
    const array per_page_options  = [15, 25, 50, 100];

    protected Builder  $query;
    protected array    $columns             = [];
    protected array    $filtres             = [];
    protected array    $valeurs_filtres     = [];
    protected ?string  $search              = null;
    protected ?string  $tri_par             = null;
    protected string   $tri_direction       = 'asc';
    protected int      $per_page            = self::default_per_page;
    protected ?Closure $row_url             = null;
    protected ?Closure $export_personnalise = null;
    protected string   $nom_export          = 'export';
    protected bool     $avec_modification   = true;
    protected bool     $avec_suppression    = true;



    public static function new(Builder $query) : static {
        $table_builder = new static();

        $table_builder->query = $query;


        //==============================================================================================================
        // Paramètres envoyés par le tableau Vue (?search=...&tri_par=...&tri_direction=...&per_page=...&filtres[...]=...)
        //==============================================================================================================
        $table_builder->search        = request('search');
        $table_builder->tri_par       = request('tri_par');
        $table_builder->tri_direction = request('tri_direction') === 'desc' ? 'desc' : 'asc';
        $table_builder->per_page      = min(max((int) request('per_page', self::default_per_page), 1), self::max_per_page);
        $table_builder->nom_export    = Str::slug(request()->path()) ?: 'export';

        return $table_builder;
    }



    public function add_column(TableColumn $column) : static {
        $this->columns[] = $column;

        return $this;
    }



    public function add_filtre(TableFilter $filtre) : static {
        $this->filtres[] = $filtre;

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



    public function nom_export(string $nom_export) : static {
        $this->nom_export = $nom_export;

        return $this;
    }



    //==================================================================================================================
    // Export Excel sur mesure : fn(Builder $query) => BinaryFileResponse
    // La requête reçue a déjà la recherche, les filtres et le tri de la liste
    //==================================================================================================================
    public function export_avec(Closure $export) : static {
        $this->export_personnalise = $export;

        return $this;
    }



    //==================================================================================================================
    // Menu "⋯" de chaque ligne : Consulter seulement (ex. "Mes séances", journal)
    //==================================================================================================================
    public function actions_consultation_seule() : static {
        $this->avec_modification = false;
        $this->avec_suppression  = false;

        return $this;
    }



    public function get() : array {
        //==============================================================================================================
        // ?export=xlsx : on renvoie le fichier Excel à la place de la page (BF-28)
        //==============================================================================================================
        if (request('export') === 'xlsx') {
            throw new HttpResponseException($this->export_excel());
        }

        $this->apply_filtres();
        $this->apply_search();
        $this->apply_tri();


        //==============================================================================================================
        // Pagination
        //==============================================================================================================
        $paginator = $this->query
            ->paginate($this->per_page)
            ->withQueryString();


        //==============================================================================================================
        // Lignes : valeurs des colonnes (+ couleurs et secondes lignes), URL au clic, actions du menu "⋯"
        //==============================================================================================================
        $items = collect($paginator->items())->map(function (Model $model) {
            $url = $this->row_url ? ($this->row_url)($model) : null;

            return [
                'cle'     => $model->getAttribute('cle'),
                'valeurs' => $this->get_valeurs($model),
                'url'     => $url,
                'actions' => $url ? $this->get_actions($model, $url) : null,
            ];
        });

        return [
            'headers'       => array_map(fn(TableColumn $column) => $column->get(), $this->columns),
            'items'         => $items,
            'filtres'       => array_map(fn(TableFilter $filtre) => $filtre->get($this->valeurs_filtres[$filtre->get_nom()] ?? null), $this->filtres),
            'search'        => $this->search,
            'tri_par'       => $this->tri_par,
            'tri_direction' => $this->tri_direction,
            'pagination'    => [
                'page'      => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total'     => $paginator->total(),
                'per_page'  => $paginator->perPage(),
                'options'   => self::per_page_options,
            ],
        ];
    }



    //==================================================================================================================
    // Filtres : uniquement ceux déclarés, avec des valeurs normalisées (jamais de colonne libre venant de l'URL)
    //==================================================================================================================
    protected function apply_filtres() : void {
        $brut = request('filtres', []);
        $brut = is_array($brut) ? $brut : [];

        foreach ($this->filtres as $filtre) {
            $valeur = $filtre->normaliser($brut[$filtre->get_nom()] ?? null);

            $this->valeurs_filtres[$filtre->get_nom()] = $valeur;

            if ($valeur !== null) {
                $filtre->appliquer_sur($this->query, $valeur);
            }
        }
    }



    //==================================================================================================================
    // Actions d'une ligne
    // Modifier : permis sauf si le model l'interdit (can_be_updated) ; Supprimer : seulement si can_be_deleted() le permet
    //==================================================================================================================
    protected function get_actions(Model $model, string $url) : array {
        $peut_modifier = $this->avec_modification
            && (!method_exists($model, 'can_be_updated') || $model->can_be_updated());

        $peut_supprimer = $this->avec_suppression
            && method_exists($model, 'can_be_deleted')
            && $model->can_be_deleted();

        return [
            'label'         => method_exists($model, 'get_label') ? (string) $model->get_label() : null,
            'url_consulter' => $url,
            'url_modifier'  => $peut_modifier ? "{$url}/edit" : null,
            'url_supprimer' => $peut_supprimer ? $url : null,
        ];
    }



    protected function apply_search() : void {
        //==============================================================================================================
        // Recherche "LIKE %texte%" sur les colonnes cherchables ; "relation.colonne" cherche dans la relation
        //==============================================================================================================
        $colonnes = [];

        foreach ($this->columns as $column) {
            if ($column->is_cherchable()) {
                array_push($colonnes, ...$column->get_colonnes_recherche());
            }
        }

        if (!$this->search || !$colonnes) {
            return;
        }

        $this->query->where(function (Builder $query) use ($colonnes) {
            foreach ($colonnes as $colonne) {
                if (str_contains($colonne, '.')) {
                    $query->orWhereHas(
                        Str::beforeLast($colonne, '.'),
                        fn(Builder $relation) => $relation->where(Str::afterLast($colonne, '.'), 'like', "%{$this->search}%")
                    );
                } else {
                    $query->orWhere($colonne, 'like', "%{$this->search}%");
                }
            }
        });
    }



    protected function apply_tri() : void {
        //==============================================================================================================
        // On trie uniquement sur une colonne déclarée triable (jamais un nom de colonne libre venant de l'URL)
        //==============================================================================================================
        foreach ($this->columns as $column) {
            if ($column->is_triable() && $column->get_nom_colonne() === $this->tri_par) {
                $this->query->orderBy($column->get_colonne_tri(), $this->tri_direction);

                return;
            }
        }
    }



    protected function get_valeurs(Model $model) : array {
        //==============================================================================================================
        // Valeur calculée si déclarée ; sinon toArray() (casts appliqués), sinon l'attribut (ex. horaire_render)
        // Couleur et seconde ligne : sous les clés "colonne__couleur" et "colonne__sous_texte"
        //==============================================================================================================
        $array   = $model->toArray();
        $valeurs = [];

        foreach ($this->columns as $column) {
            $nom_colonne = $column->get_nom_colonne();

            if ($column->has_valeur_calculee()) {
                $valeurs[$nom_colonne] = $column->get_valeur_calculee($model);
            } else {
                $valeurs[$nom_colonne] = array_key_exists($nom_colonne, $array)
                    ? $array[$nom_colonne]
                    : data_get($model, $nom_colonne);
            }

            if ($column->get_couleur()) {
                $valeurs["{$nom_colonne}__couleur"] = data_get($model, $column->get_couleur());
            }

            if ($column->get_sous_texte()) {
                $valeurs["{$nom_colonne}__sous_texte"] = data_get($model, $column->get_sous_texte());
            }
        }

        return $valeurs;
    }



    protected function export_excel() : BinaryFileResponse {
        $this->apply_filtres();
        $this->apply_search();
        $this->apply_tri();


        //==============================================================================================================
        // Export sur mesure (ex. étudiants au format d'import), sinon les colonnes du tableau
        //==============================================================================================================
        if ($this->export_personnalise) {
            return ($this->export_personnalise)($this->query->limit(self::max_lignes_export));
        }

        $lignes = [];

        foreach ($this->query->limit(self::max_lignes_export)
                             ->get() as $model) {
            $valeurs = $this->get_valeurs($model);
            $ligne   = [];

            foreach ($this->columns as $column) {
                $nom_colonne = $column->get_nom_colonne();

                $ligne[] = self::formater_pour_export(
                    $valeurs[$nom_colonne] ?? null,
                    $column->get_render(),
                    $valeurs["{$nom_colonne}__sous_texte"] ?? null
                );
            }

            $lignes[] = $ligne;
        }

        $entetes = array_map(fn(TableColumn $column) => $column->get_label(), $this->columns);

        return Excel::download(
            new TableauExport($entetes, $lignes, Str::headline($this->nom_export)),
            "{$this->nom_export}-" . now()->format('Y-m-d') . '.xlsx'
        );
    }



    protected static function formater_pour_export(mixed $valeur, string $render, mixed $sous_texte) : mixed {
        if ($valeur === null) {
            return '';
        }

        return match ($render) {
            RendersService::render_boolean  => $valeur ? 'Oui' : 'Non',
            RendersService::render_date     => Carbon::parse($valeur)
                                                     ->format('d/m/Y'),
            RendersService::render_statut   => ucfirst(str_replace('_', ' ', mb_strtolower((string) $valeur))),
            RendersService::render_personne => $sous_texte ? "{$valeur} ({$sous_texte})" : $valeur,
            default                         => $valeur,
        };
    }
}
