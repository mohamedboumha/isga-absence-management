<template>
    <div class="flex flex-col gap-3">
        <!--=========================================================================================================-->
        <!-- Barre d'outils : Ajouter, recherche, Filtres ... Exporter -->
        <!--=========================================================================================================-->
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex w-full flex-wrap items-center gap-3 sm:w-auto sm:flex-nowrap">
                <Button v-if="url_ajouter" as-child class="h-10 shrink-0">
                    <Link :href="url_ajouter">
                        <Plus class="size-4" />
                        {{ label_ajouter ?? 'Ajouter' }}
                    </Link>
                </Button>

                <div class="relative w-full sm:w-80">
                    <Search class="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                    <input v-model="search" type="search" placeholder="Rechercher..." aria-label="Rechercher dans la liste" class="champ h-10 pl-9 text-sm" />
                </div>

                <FiltresTable v-if="table.filtres?.length" :filtres="table.filtres" @appliquer="(valeurs) => recharger({ filtres: valeurs, page: 1 })" />
            </div>

            <div class="flex items-center gap-2">
                <slot name="actions" />

                <Button variant="outline" class="h-10" @click="exporter">
                    <FileSpreadsheet class="size-4" />
                    Exporter
                </Button>
            </div>
        </div>

        <!--=========================================================================================================-->
        <!-- Filtres actifs : une puce par filtre, retirable -->
        <!--=========================================================================================================-->
        <div v-if="puces.length" class="flex flex-wrap items-center gap-2">
            <span
                v-for="puce in puces"
                :key="puce.nom"
                class="bg-card inline-flex items-center gap-1.5 rounded-full border py-1 pr-1.5 pl-3 text-sm"
            >
                <span class="text-muted-foreground">{{ puce.label }} :</span>
                <span class="font-medium">{{ puce.texte }}</span>
                <button
                    type="button"
                    class="text-muted-foreground hover:bg-muted hover:text-foreground flex size-5 items-center justify-center rounded-full"
                    :aria-label="`Retirer le filtre ${puce.label}`"
                    @click="retirer_filtre(puce.nom)"
                >
                    <X class="size-3.5" />
                </button>
            </span>

            <button type="button" class="text-muted-foreground hover:text-foreground text-sm underline-offset-4 hover:underline" @click="recharger({ filtres: {}, page: 1 })">
                Effacer les filtres
            </button>
        </div>

        <!--=========================================================================================================-->
        <!-- Tableau : une feuille blanche posée sur le fond gris -->
        <!--=========================================================================================================-->
        <div class="bg-card overflow-hidden rounded-xl border">
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-sm">
                    <thead>
                    <tr class="bg-muted/70 border-b">
                        <th
                            v-for="header in table.headers"
                            :key="header.nom_colonne"
                            scope="col"
                            class="text-muted-foreground h-11 px-4 text-xs font-medium whitespace-nowrap"
                            :class="header.render === 'nombre' ? 'text-right' : 'text-left'"
                            :aria-sort="aria_sort(header)"
                        >
                            <button
                                v-if="header.triable"
                                type="button"
                                class="hover:text-foreground inline-flex items-center gap-1.5"
                                :class="{ 'text-foreground': table.tri_par === header.nom_colonne }"
                                @click="trier(header)"
                            >
                                {{ header.label }}
                                <component :is="icone_tri(header)" class="size-3.5" :class="{ 'opacity-40': table.tri_par !== header.nom_colonne }" />
                            </button>

                            <template v-else>{{ header.label }}</template>
                        </th>

                        <th v-if="avec_actions" scope="col" class="w-14 px-2">
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                    </thead>

                    <!--=================================================================================================-->
                    <!-- Chargement : lignes squelettes -->
                    <!--=================================================================================================-->
                    <tbody v-if="chargement">
                    <tr v-for="ligne in nb_lignes_squelette" :key="ligne" class="border-b last:border-0">
                        <td v-for="(header, index) in table.headers" :key="header.nom_colonne" class="h-14 px-4">
                            <span class="bg-muted block h-3 animate-pulse rounded" :style="{ width: `${35 + ((ligne * 7 + index * 13) % 45)}%` }" />
                        </td>
                        <td v-if="avec_actions" />
                    </tr>
                    </tbody>

                    <!--=================================================================================================-->
                    <!-- Lignes -->
                    <!--=================================================================================================-->
                    <tbody v-else>
                    <tr
                        v-for="item in table.items"
                        :key="item.cle"
                        class="border-b transition-colors last:border-0"
                        :class="item.url ? 'hover:bg-muted/50 focus-visible:bg-muted/50 cursor-pointer focus-visible:outline-none' : ''"
                        :tabindex="item.url ? 0 : undefined"
                        @click="ouvrir(item, $event)"
                        @keydown.enter.self="ouvrir(item, $event)"
                    >
                        <td
                            v-for="(header, index) in table.headers"
                            :key="header.nom_colonne"
                            class="h-14 px-4 py-2"
                            :class="[header.render === 'nombre' ? 'text-right tabular-nums' : '', index === 0 ? 'text-foreground font-medium' : '']"
                        >
                            <!-- Badge à la couleur enregistrée -->
                            <template v-if="header.render === 'badge'">
                                <BadgeCouleur
                                    v-if="valeur(item, header) !== null && valeur(item, header) !== undefined && valeur(item, header) !== ''"
                                    :couleur="item.valeurs[`${header.nom_colonne}__couleur`] ?? '#475569'"
                                    :label="String(valeur(item, header))"
                                />
                                <span v-else class="text-muted-foreground">—</span>
                            </template>

                            <!-- Pastille de statut -->
                            <template v-else-if="header.render === 'statut'">
                                <StatutPill v-if="valeur(item, header)" :statut="String(valeur(item, header))" />
                                <span v-else class="text-muted-foreground">—</span>
                            </template>

                            <!-- Personne : initiales + nom + seconde ligne -->
                            <CellulePersonne
                                v-else-if="header.render === 'personne'"
                                :nom="valeur(item, header)"
                                :sous_texte="sous_texte(item, header)"
                            />

                            <!-- Renders classiques (chaine, date, boolean, nombre) -->
                            <component
                                :is="composants[header.render]"
                                v-else
                                :mode_vue="renders.mode_list"
                                :nom_champ="header.nom_colonne"
                                :valeur="valeur(item, header)"
                            />

                            <!-- Seconde ligne (sauf personne, qui l'affiche elle-même) -->
                            <span
                                v-if="header.render !== 'personne' && sous_texte(item, header)"
                                class="text-muted-foreground mt-0.5 block truncate text-xs font-normal"
                            >
                                    {{ sous_texte(item, header) }}
                                </span>
                        </td>

                        <!-- Menu "⋯" (le clic ne déclenche pas l'ouverture de la ligne) -->
                        <td v-if="avec_actions" class="px-2 text-right" @click.stop @keydown.stop>
                            <ActionsLigne v-if="item.actions" :actions="item.actions" />
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <!--=====================================================================================================-->
            <!-- Aucun résultat -->
            <!--=====================================================================================================-->
            <div v-if="!chargement && !table.items.length" class="flex flex-col items-center gap-2 px-6 py-14 text-center">
                <SearchX v-if="table.search || puces.length" class="text-muted-foreground size-8" />
                <Inbox v-else class="text-muted-foreground size-8" />

                <p class="font-medium">{{ titre_vide }}</p>
                <p class="text-muted-foreground max-w-sm text-sm">{{ message_vide }}</p>

                <div class="mt-2 flex gap-2">
                    <Button v-if="table.search" variant="outline" size="sm" @click="search = ''">Effacer la recherche</Button>
                    <Button v-if="puces.length" variant="outline" size="sm" @click="recharger({ filtres: {}, page: 1 })">Effacer les filtres</Button>
                </div>
            </div>
        </div>

        <!--=========================================================================================================-->
        <!-- Pagination -->
        <!--=========================================================================================================-->
        <PaginationTable
            :pagination="table.pagination"
            @page="(page) => recharger({ page })"
            @per_page="(per_page) => recharger({ per_page, page: 1 })"
        />
    </div>
</template>


<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { ArrowDown, ArrowUp, ArrowUpDown, FileSpreadsheet, Inbox, Plus, Search, SearchX, X } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import BadgeCouleur from '@/_core/renders/badge-couleur.vue';
import ChampBoolean from '@/_core/renders/champ-boolean.vue';
import ChampChaine from '@/_core/renders/champ-chaine.vue';
import ChampDate from '@/_core/renders/champ-date.vue';
import ChampNombre from '@/_core/renders/champ-nombre.vue';
import StatutPill from '@/_core/renders/statut-pill.vue';
import { renders } from '@/_core/renders';
import ActionsLigne from './actions-ligne.vue';
import CellulePersonne from './cellule-personne.vue';
import FiltresTable from './filtres-table.vue';
import PaginationTable from './pagination-table.vue';
import type { Table, TableHeader, TableItem, ValeurPeriode } from './types';

interface DataTableInterface {
    table: Table;
    url_ajouter?: string | null;
    label_ajouter?: string;
}

const props = defineProps<DataTableInterface>();

//==============================================================================================================
// render (PHP)  =>  composant Vue (renders classiques)
//==============================================================================================================
const composants: Record<string, unknown> = {
    chaine: ChampChaine,
    date: ChampDate,
    boolean: ChampBoolean,
    nombre: ChampNombre,
};

const valeur = (item: TableItem, header: TableHeader) => item.valeurs[header.nom_colonne];
const sous_texte = (item: TableItem, header: TableHeader) => item.valeurs[`${header.nom_colonne}__sous_texte`] ?? null;

//==============================================================================================================
// Colonne "⋯" : affichée dès qu'une ligne a des actions
//==============================================================================================================
const avec_actions = computed(() => props.table.items.some((item) => item.actions));

//==============================================================================================================
// Filtres actifs : leurs valeurs (pour l'URL) et leurs puces ("Groupe : 1AP-A")
//==============================================================================================================
const valeurs_filtres = computed(() =>
    Object.fromEntries((props.table.filtres ?? []).filter((filtre) => filtre.valeur !== null).map((filtre) => [filtre.nom, filtre.valeur])),
);

const date_courte = (date: string | null) => (date ? date.split('-').reverse().join('/') : '');

const puces = computed(() =>
    (props.table.filtres ?? [])
        .filter((filtre) => filtre.valeur !== null)
        .map((filtre) => {
            let texte = '';

            if (filtre.type === 'periode') {
                const { du, au } = filtre.valeur as ValeurPeriode;

                texte = du && au ? `du ${date_courte(du)} au ${date_courte(au)}` : du ? `à partir du ${date_courte(du)}` : `jusqu'au ${date_courte(au)}`;
            } else {
                texte = filtre.options.find((option) => String(option.valeur) === String(filtre.valeur))?.label ?? String(filtre.valeur);
            }

            return { nom: filtre.nom, label: filtre.label, texte };
        }),
);

const retirer_filtre = (nom: string) => {
    const valeurs = { ...valeurs_filtres.value };

    delete valeurs[nom];

    recharger({ filtres: valeurs, page: 1 });
};

//==============================================================================================================
// Message quand la liste est vide (recherche, filtres, ou vraiment vide)
//==============================================================================================================
const titre_vide = computed(() => {
    if (props.table.search) return `Aucun résultat pour « ${props.table.search} »`;
    if (puces.value.length) return 'Aucun résultat avec ces filtres';

    return 'Aucun élément pour le moment';
});

const message_vide = computed(() =>
    props.table.search || puces.value.length ? "Modifiez la recherche ou les filtres pour élargir la liste." : 'Les éléments ajoutés apparaîtront ici.',
);

//==============================================================================================================
// Chargement : squelette seulement si la réponse tarde (> 150 ms), pour éviter un clignotement
//==============================================================================================================
const chargement = ref(false);
let minuteur_chargement: ReturnType<typeof setTimeout> | undefined;

const nb_lignes_squelette = computed(() => Math.min(Math.max(props.table.items.length, 5), 15));

//==============================================================================================================
// Recharge la page avec les nouveaux paramètres, en gardant ceux de la page (ex. ?statut=VALIDE)
// Les filtres sont toujours envoyés en entier : ceux de la table, ou ceux passés en paramètre
//==============================================================================================================
const recharger = (params: Record<string, unknown>) => {
    const actuels = Object.fromEntries(
        [...new URLSearchParams(window.location.search)].filter(([cle]) => cle !== 'export' && !cle.startsWith('filtres[')),
    );

    clearTimeout(minuteur_chargement);
    minuteur_chargement = setTimeout(() => (chargement.value = true), 150);

    router.get(
        window.location.pathname,
        {
            ...actuels,
            search: props.table.search || undefined,
            tri_par: props.table.tri_par || undefined,
            tri_direction: props.table.tri_direction,
            page: props.table.pagination.page,
            per_page: props.table.pagination.per_page,
            filtres: valeurs_filtres.value,
            ...params,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onFinish: () => {
                clearTimeout(minuteur_chargement);
                chargement.value = false;
            },
        },
    );
};

//==============================================================================================================
// Recherche (attend 300 ms après la dernière frappe)
//==============================================================================================================
const search = ref(props.table.search ?? '');
let minuteur_recherche: ReturnType<typeof setTimeout>;

watch(search, (texte) => {
    clearTimeout(minuteur_recherche);
    minuteur_recherche = setTimeout(() => recharger({ search: texte || undefined, page: 1 }), 300);
});

//==============================================================================================================
// Tri : clic => croissant, re-clic => décroissant
//==============================================================================================================
const trier = (header: TableHeader) => {
    const direction = props.table.tri_par === header.nom_colonne && props.table.tri_direction === 'asc' ? 'desc' : 'asc';

    recharger({ tri_par: header.nom_colonne, tri_direction: direction, page: 1 });
};

const icone_tri = (header: TableHeader) => {
    if (props.table.tri_par !== header.nom_colonne) return ArrowUpDown;

    return props.table.tri_direction === 'asc' ? ArrowUp : ArrowDown;
};

const aria_sort = (header: TableHeader) => {
    if (!header.triable || props.table.tri_par !== header.nom_colonne) return undefined;

    return props.table.tri_direction === 'asc' ? 'ascending' : 'descending';
};

//==============================================================================================================
// Ouvrir une ligne ; Cmd/Ctrl + clic : nouvel onglet
//==============================================================================================================
const ouvrir = (item: TableItem, event: MouseEvent | KeyboardEvent) => {
    if (!item.url) return;

    if (event.metaKey || event.ctrlKey) {
        window.open(item.url, '_blank');
        return;
    }

    router.visit(item.url);
};

//==============================================================================================================
// Export Excel : même URL (recherche, tri, filtres) + export=xlsx ; le navigateur télécharge le fichier
//==============================================================================================================
const exporter = () => {
    const params = new URLSearchParams(window.location.search);

    params.set('export', 'xlsx');

    window.location.href = `${window.location.pathname}?${params.toString()}`;
};
</script>
