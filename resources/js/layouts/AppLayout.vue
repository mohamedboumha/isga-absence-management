<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItem } from '@/types';

const { breadcrumbs = [] } = defineProps<{
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

<template>
    <AppLayout :breadcrumbs="breadcrumbs_affiches">
        <slot />
    </AppLayout>
</template>
