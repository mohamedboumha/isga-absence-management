<template>
    <Head :title="titre_page"/>

    <div class="flex flex-col gap-6 p-4">
        <div>
            <h1 class="text-xl font-semibold">{{ titre_page }}</h1>
            <p class="text-muted-foreground text-sm">
                Toutes les créations, modifications et suppressions, enregistrées automatiquement. Le journal ne peut
                pas être modifié.
            </p>
        </div>

        <DataTable :table="table"/>
    </div>
</template>


<script setup lang="ts">
import {Head} from '@inertiajs/vue3';
import DataTable from '@/_core/table/data-table.vue';
import type {ModeVue} from '@/_core/renders';
import type {Table} from '@/_core/table/types';

interface JournalListInterface {
    mode_vue: ModeVue;
    titre_page: string;
    table: Table;
}

const props = defineProps<JournalListInterface>();
</script>
