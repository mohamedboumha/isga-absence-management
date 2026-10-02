<template>
    <div v-for="annee in structure" :key="annee.annee"
         class="flex flex-col gap-3 border-b px-5 py-4 last:border-0 sm:flex-row sm:items-start">
        <span class="text-muted-foreground w-28 shrink-0 pt-1 text-xs font-medium">{{ annee.label }}</span>

        <div v-if="annee.niveaux.length" class="flex flex-1 flex-wrap gap-3">
            <Link
                v-for="niveau in annee.niveaux"
                :key="niveau.url"
                :href="niveau.url"
                class="hover:bg-muted/50 flex min-w-44 flex-col gap-1.5 rounded-lg border px-3 py-2.5"
                :title="niveau.libelle"
            >
                <BadgeCouleur :couleur="niveau.couleur" :label="niveau.code" class="self-start"/>
                <span class="text-muted-foreground text-xs tabular-nums">
                    {{ niveau.nb_groupes }} groupe(s), {{ niveau.effectif }} étudiant(s)
                </span>
            </Link>
        </div>

        <span v-else class="text-attente pt-1 text-sm">Aucun niveau défini pour cette année.</span>
    </div>
</template>


<script setup lang="ts">
import {Link} from '@inertiajs/vue3';
import BadgeCouleur from '@/_core/renders/badge-couleur.vue';
import type {AnneeStructure} from './types';

interface StructureNiveauxInterface {
    structure: AnneeStructure[];
}

const props = defineProps<StructureNiveauxInterface>();
</script>
