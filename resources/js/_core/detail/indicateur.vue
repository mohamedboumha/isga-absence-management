<template>
    <div class="flex flex-col gap-0.5">
        <span class="text-muted-foreground text-xs">{{ label }}</span>
        <span class="text-2xl leading-tight font-semibold tabular-nums" :class="classes[tonalite]">
            {{ valeur }}<span v-if="unite" class="ml-0.5 text-base font-medium">{{ unite }}</span>
        </span>
        <span v-if="detail" class="text-muted-foreground text-xs tabular-nums">{{ detail }}</span>
    </div>
</template>


<script setup lang="ts">
import type {Tonalite} from '@/_core/renders/statuts';

interface IndicateurInterface {
    label: string;
    valeur: string | number;
    unite?: string;
    detail?: string;
    tonalite?: Tonalite;
}

const props = withDefaults(defineProps<IndicateurInterface>(), {
    unite: undefined,
    detail: undefined,
    tonalite: 'neutre',
});

const classes: Record<Tonalite, string> = {
    present: 'text-present',
    absent: 'text-absent',
    attente: 'text-attente',
    justifie: 'text-justifie',
    neutre: 'text-foreground',
};
</script>
