<template>
    <Head :title="titre_page"/>

    <div class="flex flex-col gap-6 p-4">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold">{{ titre_page }}</h1>

            <Button as-child>
                <Link :href="url_create">Ajouter un justificatif</Link>
            </Button>
        </div>

        <!--=====================================================================================================-->
        <!-- Onglets par statut (BF-22) -->
        <!--=====================================================================================================-->
        <div class="flex gap-1 border-b">
            <Link
                v-for="onglet in onglets"
                :key="onglet.valeur"
                :href="`/justificatifs?statut=${onglet.valeur}`"
                class="-mb-px border-b-2 px-3 py-2 text-sm"
                :class="statut === onglet.valeur ? 'border-primary font-medium' : 'text-muted-foreground border-transparent'"
            >
                {{ onglet.label }}
                <span v-if="onglet.total !== null" class="bg-muted ml-1 rounded-full px-1.5 text-xs">{{
                        onglet.total
                    }}</span>
            </Link>
        </div>

        <DataTable :table="table"/>
    </div>
</template>


<script setup lang="ts">
import {computed} from 'vue';
import {Head, Link} from '@inertiajs/vue3';
import {Button} from '@/components/ui/button';
import DataTable from '@/_core/table/data-table.vue';
import type {ModeVue} from '@/_core/renders';
import type {Table} from '@/_core/table/types';

interface JustificatifListInterface {
    mode_vue: ModeVue;
    titre_page: string;
    table: Table;
    statut: string;
    statuts: Record<string, string>;
    compteurs: Record<string, number>;
    url_create: string;
}

const props = defineProps<JustificatifListInterface>();

const onglets = computed(() => [
    ...Object.entries(props.statuts).map(([valeur, label]) => ({
        valeur,
        label,
        total: props.compteurs[valeur] ?? 0,
    })),
    {valeur: 'TOUS', label: 'Tous', total: null},
]);
</script>
