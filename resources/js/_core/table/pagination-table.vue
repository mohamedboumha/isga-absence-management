<template>
    <div class="text-muted-foreground grid items-center gap-3 text-sm md:grid-cols-[1fr_auto_1fr]">
        <!--=========================================================================================================-->
        <!-- Position : "1–15 sur 248" -->
        <!--=========================================================================================================-->
        <p class="tabular-nums">
            <template v-if="pagination.total">{{ debut }}–{{ fin }} sur {{ pagination.total }}</template>
            <template v-else>Aucun élément</template>
        </p>

        <!--=========================================================================================================-->
        <!-- Lignes par page : au centre, seulement les choix utiles pour cette liste -->
        <!--=========================================================================================================-->
        <div v-if="choix_lignes.length" class="flex items-center gap-2 md:justify-center">
            <span>Lignes par page</span>

            <div class="bg-champ flex rounded-md border p-0.5" role="group" aria-label="Lignes par page">
                <button
                    v-for="choix in choix_lignes"
                    :key="choix.label"
                    type="button"
                    class="min-w-10 rounded-[calc(var(--radius-md)-2px)] px-2.5 py-1 tabular-nums transition-colors"
                    :class="choix.actif ? 'bg-primary text-primary-foreground font-semibold shadow-xs' : 'hover:text-foreground'"
                    :aria-pressed="choix.actif"
                    @click="!choix.actif && emit('per_page', choix.valeur)"
                >
                    {{ choix.label }}
                </button>
            </div>
        </div>

        <span v-else class="hidden md:block"/>

        <!--=========================================================================================================-->
        <!-- Pages -->
        <!--=========================================================================================================-->
        <nav v-if="pagination.last_page > 1" class="flex items-center gap-1 md:justify-end" aria-label="Pagination">
            <button
                type="button"
                class="hover:bg-muted flex size-9 items-center justify-center rounded-md disabled:pointer-events-none disabled:opacity-40"
                :disabled="pagination.page <= 1"
                aria-label="Page précédente"
                @click="emit('page', pagination.page - 1)"
            >
                <ChevronLeft class="size-4"/>
            </button>

            <template v-for="(element, index) in pages" :key="index">
                <span v-if="element === '…'" class="flex size-9 items-center justify-center">…</span>

                <button
                    v-else
                    type="button"
                    class="flex size-9 items-center justify-center rounded-md tabular-nums"
                    :class="element === pagination.page ? 'bg-primary text-primary-foreground font-semibold' : 'hover:bg-muted text-foreground'"
                    :aria-current="element === pagination.page ? 'page' : undefined"
                    @click="emit('page', element)"
                >
                    {{ element }}
                </button>
            </template>

            <button
                type="button"
                class="hover:bg-muted flex size-9 items-center justify-center rounded-md disabled:pointer-events-none disabled:opacity-40"
                :disabled="pagination.page >= pagination.last_page"
                aria-label="Page suivante"
                @click="emit('page', pagination.page + 1)"
            >
                <ChevronRight class="size-4"/>
            </button>
        </nav>

        <span v-else class="hidden md:block"/>
    </div>
</template>


<script setup lang="ts">
import {computed} from 'vue';
import {ChevronLeft, ChevronRight} from '@lucide/vue';
import type {TablePagination} from './types';

interface PaginationTableInterface {
    pagination: TablePagination;
}

interface ChoixLignes {
    valeur: number;
    label: string;
    actif: boolean;
}

const props = defineProps<PaginationTableInterface>();

const emit = defineEmits<{
    page: [page: number];
    per_page: [per_page: number];
}>();

const debut = computed(() => (props.pagination.page - 1) * props.pagination.per_page + 1);
const fin = computed(() => Math.min(props.pagination.page * props.pagination.per_page, props.pagination.total));

//==============================================================================================================
// Lignes par page, selon la taille de la liste :
// - 15 éléments ou moins : rien à choisir (tout tient sur une page)
// - sinon : les tailles plus petites que la liste, puis "Tous" si la liste ne dépasse pas la taille maximale
//==============================================================================================================
const choix_lignes = computed<ChoixLignes[]>(() => {
    const {total, per_page, options} = props.pagination;
    const plus_petite = Math.min(...options);
    const plus_grande = Math.max(...options);

    if (total <= plus_petite) {
        return [];
    }

    const tout_tient = per_page >= total;

    const choix: ChoixLignes[] = options
        .filter((option) => option < total)
        .map((option) => ({valeur: option, label: String(option), actif: !tout_tient && option === per_page}));

    if (total <= plus_grande) {
        choix.push({valeur: total, label: 'Tous', actif: tout_tient});
    }

    return choix;
});

//==============================================================================================================
// 1 … 4 5 6 … 17 : la première, la dernière, et les pages autour de la page courante
//==============================================================================================================
const pages = computed<(number | '…')[]>(() => {
    const {page, last_page} = props.pagination;

    if (last_page <= 7) {
        return Array.from({length: last_page}, (_, index) => index + 1);
    }

    const liste: (number | '…')[] = [1];
    const debut_fenetre = Math.max(2, page - 1);
    const fin_fenetre = Math.min(last_page - 1, page + 1);

    if (debut_fenetre > 2) liste.push('…');

    for (let numero = debut_fenetre; numero <= fin_fenetre; numero++) {
        liste.push(numero);
    }

    if (fin_fenetre < last_page - 1) liste.push('…');

    liste.push(last_page);

    return liste;
});
</script>
