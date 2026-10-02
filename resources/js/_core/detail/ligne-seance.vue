<template>
    <Link :href="seance.url"
          class="hover:bg-muted/50 flex flex-wrap items-center gap-x-4 gap-y-1 border-b px-5 py-3 text-sm last:border-0">
        <span class="w-36 shrink-0 tabular-nums">
            <span class="font-medium">{{ seance.date }}</span>
            <span class="text-muted-foreground block text-xs">{{ seance.horaire }}</span>
        </span>

        <span class="flex min-w-0 flex-1 items-center gap-2">
            <BadgeCouleur :couleur="seance.couleur" :label="seance.module"/>
            <span class="truncate">{{ seance.intitule }}</span>
            <span class="text-muted-foreground shrink-0 text-xs">{{ seance.type }}</span>
        </span>

        <span v-if="seance.groupe || seance.enseignant" class="text-muted-foreground hidden w-40 truncate md:block">
            {{ seance.groupe ?? seance.enseignant }}
        </span>

        <StatutPill :statut="seance.statut"/>
    </Link>
</template>


<script setup lang="ts">
import {Link} from '@inertiajs/vue3';
import BadgeCouleur from '@/_core/renders/badge-couleur.vue';
import StatutPill from '@/_core/renders/statut-pill.vue';
import type {SeanceResume} from './types';

interface LigneSeanceInterface {
    seance: SeanceResume;
}

const props = defineProps<LigneSeanceInterface>();
</script>
