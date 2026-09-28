<script setup lang="ts">
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import ChampBoolean from '@/_core/renders/champ-boolean.vue';
import ChampChaine from '@/_core/renders/champ-chaine.vue';
import ChampDate from '@/_core/renders/champ-date.vue';
import { renders } from '@/_core/renders';
import type { Table, TableHeader } from './types';

const props = defineProps<{ table: Table }>();

//==============================================================================================================
// render (PHP)  =>  composant Vue
//==============================================================================================================
const composants = {
    chaine: ChampChaine,
    date: ChampDate,
    boolean: ChampBoolean,
};

//==============================================================================================================
// Recharge la page avec les nouveaux paramètres (recherche, tri, page)
//==============================================================================================================
const recharger = (params: Record<string, any>) => {
    router.get(
        window.location.pathname,
        {
            search: props.table.search || undefined,
            tri_par: props.table.tri_par || undefined,
            tri_direction: props.table.tri_direction,
            page: props.table.pagination.page,
            ...params,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

//==============================================================================================================
// Recherche (attend 300 ms après la dernière frappe)
//==============================================================================================================
const search = ref(props.table.search ?? '');
let timer: ReturnType<typeof setTimeout>;

watch(search, (valeur) => {
    clearTimeout(timer);
    timer = setTimeout(() => recharger({ search: valeur || undefined, page: 1 }), 300);
});

//==============================================================================================================
// Tri : clic sur une colonne triable => asc, re-clic => desc
//==============================================================================================================
const trier = (header: TableHeader) => {
    if (!header.triable) return;

    const direction = props.table.tri_par === header.nom_colonne && props.table.tri_direction === 'asc' ? 'desc' : 'asc';

    recharger({ tri_par: header.nom_colonne, tri_direction: direction, page: 1 });
};

const icone_tri = (header: TableHeader) => {
    if (props.table.tri_par !== header.nom_colonne) return '';

    return props.table.tri_direction === 'asc' ? '▲' : '▼';
};
</script>

<template>
    <div class="flex flex-col gap-4">
        <!--=====================================================================================================-->
        <!-- Recherche -->
        <!--=====================================================================================================-->
        <Input v-model="search" placeholder="Rechercher..." class="max-w-xs" />

        <!--=====================================================================================================-->
        <!-- Tableau -->
        <!--=====================================================================================================-->
        <div class="overflow-x-auto rounded-lg border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left">
                <tr>
                    <th
                        v-for="header in table.headers"
                        :key="header.nom_colonne"
                        class="px-4 py-3 font-medium select-none"
                        :class="{ 'cursor-pointer hover:text-foreground': header.triable }"
                        @click="trier(header)"
                    >
                        {{ header.label }} <span class="text-xs">{{ icone_tri(header) }}</span>
                    </th>
                </tr>
                </thead>

                <tbody>
                <tr
                    v-for="item in table.items"
                    :key="item.cle"
                    class="border-t hover:bg-muted/30"
                    :class="{ 'cursor-pointer': item.url }"
                    @click="item.url && router.visit(item.url)"
                >
                    <td v-for="header in table.headers" :key="header.nom_colonne" class="px-4 py-3">
                        <component
                            :is="composants[header.render]"
                            :mode_vue="renders.mode_list"
                            :nom_champ="header.nom_colonne"
                            :valeur="item.valeurs[header.nom_colonne]"
                        />
                    </td>
                </tr>

                <tr v-if="!table.items.length">
                    <td :colspan="table.headers.length" class="text-muted-foreground px-4 py-8 text-center">
                        Aucun résultat
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

        <!--=====================================================================================================-->
        <!-- Pagination -->
        <!--=====================================================================================================-->
        <div class="text-muted-foreground flex items-center justify-between text-sm">
            <span>{{ table.pagination.total }} élément(s)</span>

            <div class="flex items-center gap-2">
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="table.pagination.page <= 1"
                    @click="recharger({ page: table.pagination.page - 1 })"
                >
                    Précédent
                </Button>

                <span>Page {{ table.pagination.page }} / {{ table.pagination.last_page }}</span>

                <Button
                    variant="outline"
                    size="sm"
                    :disabled="table.pagination.page >= table.pagination.last_page"
                    @click="recharger({ page: table.pagination.page + 1 })"
                >
                    Suivant
                </Button>
            </div>
        </div>
    </div>
</template>
