<template>
    <header
        class="border-sidebar-border/70 flex h-16 shrink-0 items-center justify-between gap-3 border-b px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex min-w-0 items-center gap-2">
            <SidebarTrigger class="-ml-1" aria-label="Afficher ou masquer le menu"/>

            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs"/>
            </template>
        </div>

        <div v-if="est_connecte" class="flex shrink-0 items-center gap-2">
            <!--=================================================================================================-->
            <!-- Fiches consultées récemment -->
            <!--=================================================================================================-->
            <HistoriqueMenu/>

            <!--=================================================================================================-->
            <!-- Année en cours : tous les chiffres de l'application en dépendent -->
            <!--=================================================================================================-->
            <span
                v-if="annee_active"
                class="bg-champ text-muted-foreground hidden shrink-0 items-center gap-1.5 rounded-full border px-3 py-1 text-xs sm:inline-flex"
                title="Année universitaire en cours"
            >
                <CalendarRange class="size-3.5"/>
                Année
                <span class="text-foreground font-semibold tabular-nums">{{ annee_active }}</span>
            </span>

            <span
                v-else
                class="bg-attente/10 text-attente ring-attente/25 hidden shrink-0 rounded-full px-3 py-1 text-xs font-medium ring-1 ring-inset sm:inline-flex"
            >
                Aucune année active
            </span>
        </div>
    </header>
</template>


<script setup lang="ts">
import {computed} from 'vue';
import {usePage} from '@inertiajs/vue3';
import {CalendarRange} from '@lucide/vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import {SidebarTrigger} from '@/components/ui/sidebar';
import HistoriqueMenu from '@/_core/navigation/historique-menu.vue';
import type {BreadcrumbItem} from '@/types';

interface AppSidebarHeaderInterface {
    breadcrumbs?: BreadcrumbItem[];
}

const props = withDefaults(defineProps<AppSidebarHeaderInterface>(), {
    breadcrumbs: () => [],
});

const page = usePage();

const annee_active = computed(() => page.props.annee_active as string | null);
const est_connecte = computed(() => Boolean(page.props.auth?.user));
</script>
