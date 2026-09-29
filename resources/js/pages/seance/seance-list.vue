<template>
    <Head :title="titre_page" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold">{{ titre_page }}</h1>

            <Button as-child>
                <Link :href="url_create">Planifier une séance</Link>
            </Button>
        </div>

        <DataTable :table="table" />
    </div>
</template>


<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import DataTable from '@/_core/table/data-table.vue';
import type { ModeVue } from '@/_core/renders';
import type { Table } from '@/_core/table/types';

interface SeanceListInterface {
    mode_vue: ModeVue;
    titre_page: string;
    table: Table;
    url_create: string;
}

const props = defineProps<SeanceListInterface>();
</script>
