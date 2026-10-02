<template>
    <AppLayout :breadcrumbs="breadcrumbs_affiches">
        <slot/>

        <ConfirmationDialog/>
        <VisionneuseDocument/>
    </AppLayout>
</template>


<script setup lang="ts">
import {computed} from 'vue';
import {usePage} from '@inertiajs/vue3';
import ConfirmationDialog from '@/_core/dialogs/confirmation-dialog.vue';
import VisionneuseDocument from '@/_core/documents/visionneuse-document.vue';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type {BreadcrumbItem} from '@/types';

const {breadcrumbs = []} = defineProps<{
    breadcrumbs?: BreadcrumbItem[];
}>();

//==============================================================================================================
// Breadcrumbs : ceux de defineOptions (pages du kit) sinon ceux envoyés par le controller (nos features)
//==============================================================================================================
const page = usePage();

const breadcrumbs_affiches = computed(() =>
    breadcrumbs.length ? breadcrumbs : ((page.props.breadcrumbs as BreadcrumbItem[] | undefined) ?? []),
);
</script>
