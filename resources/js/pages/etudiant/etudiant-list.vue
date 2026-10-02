<template>
    <Head :title="titre_page"/>

    <div class="flex flex-col gap-6 p-4">
        <h1 class="text-xl font-semibold">{{ titre_page }}</h1>

        <DataTable :table="table" :url_ajouter="url_create" label_ajouter="Ajouter un étudiant">
            <template #actions>
                <Button as-child variant="outline" class="h-10">
                    <Link href="/etudiants/import">
                        <Upload class="size-4"/>
                        Importer
                    </Link>
                </Button>
            </template>
        </DataTable>
    </div>
</template>


<script setup lang="ts">
import {Head, Link} from '@inertiajs/vue3';
import {Upload} from '@lucide/vue';
import {Button} from '@/components/ui/button';
import DataTable from '@/_core/table/data-table.vue';
import type {ModeVue} from '@/_core/renders';
import type {Table} from '@/_core/table/types';

interface EtudiantListInterface {
    mode_vue: ModeVue;
    titre_page: string;
    table: Table;
    url_create: string;
}

const props = defineProps<EtudiantListInterface>();
</script>
