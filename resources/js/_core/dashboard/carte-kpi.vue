<template>
    <component
        :is="href ? Link : 'div'"
        :href="href"
        class="flex flex-col gap-1 rounded-xl border p-4"
        :class="[
            classe_tonalite,
            { 'transition-colors hover:bg-muted/40': href },
        ]"
    >
        <span class="text-sm text-muted-foreground">{{ titre }}</span>
        <span class="text-2xl font-semibold tabular-nums">{{ valeur }}</span>
        <span v-if="sous_titre" class="text-xs text-muted-foreground">{{
            sous_titre
        }}</span>
    </component>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

interface CarteKpiInterface {
    titre: string;
    valeur: string | number;
    sous_titre?: string;
    tonalite?: 'neutre' | 'alerte' | 'succes';
    href?: string;
}

const props = withDefaults(defineProps<CarteKpiInterface>(), {
    tonalite: 'neutre',
});

const classe_tonalite = computed(() => ({
    'border-amber-300 bg-amber-50/60 dark:bg-amber-950/20':
        props.tonalite === 'alerte',
    'border-green-300 bg-green-50/60 dark:bg-green-950/20':
        props.tonalite === 'succes',
}));
</script>
