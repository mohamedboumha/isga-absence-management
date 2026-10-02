<template>
    <span class="flex min-w-0 items-center gap-3">
        <span
            class="bg-champ text-isga-gris flex size-8 shrink-0 items-center justify-center rounded-full text-xs font-semibold"
            aria-hidden="true"
        >
            {{ initiales }}
        </span>

        <span class="min-w-0">
            <span class="text-foreground block truncate font-medium">{{ nom || '—' }}</span>
            <span v-if="sous_texte" class="text-muted-foreground block truncate text-xs">{{ sous_texte }}</span>
        </span>
    </span>
</template>


<script setup lang="ts">
import {computed} from 'vue';

interface CellulePersonneInterface {
    nom: string | null;
    sous_texte?: string | null;
}

const props = defineProps<CellulePersonneInterface>();

//==============================================================================================================
// "Salma EL IDRISSI"  =>  "SE"
//==============================================================================================================
const initiales = computed(() =>
    (props.nom ?? '')
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((mot) => mot.charAt(0).toUpperCase())
        .join(''),
);
</script>
