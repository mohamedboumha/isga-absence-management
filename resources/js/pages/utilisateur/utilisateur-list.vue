<template>
    <Head :title="titre_page"/>

    <div class="flex flex-col gap-6 p-4">
        <div>
            <h1 class="text-xl font-semibold">{{ titre_page }}</h1>
            <p class="text-muted-foreground text-sm">Les comptes enseignants se créent depuis la fiche Enseignant.</p>
        </div>

        <DataTable :table="table" :url_ajouter="url_create" label_ajouter="Ajouter un administrateur"/>
    </div>
</template>


<script setup lang="ts">
import {Head} from '@inertiajs/vue3';
import DataTable from '@/_core/table/data-table.vue';
import type {ModeVue} from '@/_core/renders';
import type {Table} from '@/_core/table/types';

interface UtilisateurListInterface {
    mode_vue: ModeVue;
    titre_page: string;
    table: Table;
    url_create: string;
}

const props = defineProps<UtilisateurListInterface>();
</script>
