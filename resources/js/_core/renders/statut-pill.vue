<template>
    <span
        class="inline-flex items-center gap-1.5 rounded-md px-2 py-0.5 text-xs font-medium whitespace-nowrap ring-1 ring-inset"
        :class="classes[definition.tonalite]"
    >
        <span
            class="size-1.5 shrink-0 rounded-full bg-current"
            aria-hidden="true"
        />
        {{ label ?? definition.label }}
    </span>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { statuts, type StatutDefinition, type Tonalite } from './statuts';

interface StatutPillInterface {
    statut: string;
    label?: string;
}

const props = defineProps<StatutPillInterface>();

const definition = computed<StatutDefinition>(
    () => statuts[props.statut] ?? { tonalite: 'neutre', label: props.statut },
);

//==============================================================================================================
// Fond teinté à 10 %, bordure à 25 %, texte et pastille dans la couleur du statut
//==============================================================================================================
const classes: Record<Tonalite, string> = {
    present: 'bg-present/10 text-present ring-present/25',
    absent: 'bg-absent/10 text-absent ring-absent/25',
    attente: 'bg-attente/10 text-attente ring-attente/30',
    justifie: 'bg-justifie/10 text-justifie ring-justifie/25',
    neutre: 'bg-muted text-muted-foreground ring-border',
};
</script>
