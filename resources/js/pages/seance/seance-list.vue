<template>
    <Head :title="titre_page"/>

    <div class="flex flex-col gap-6 p-4">
        <h1 class="text-xl font-semibold">{{ titre_page }}</h1>

        <DataTable :table="table" :url_ajouter="url_create" label_ajouter="Planifier une séance"/>
    </div>
</template>


<script setup lang="ts">
import {Head} from '@inertiajs/vue3';
import DataTable from '@/_core/table/data-table.vue';
import type {ModeVue} from '@/_core/renders';
import type {Table} from '@/_core/table/types';

interface SeanceListInterface {
    mode_vue: ModeVue;
    titre_page: string;
    table: Table;
    url_create: string;
}

const props = defineProps<SeanceListInterface>();
</script>
