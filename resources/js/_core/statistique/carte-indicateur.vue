<template>
    <component
        :is="href ? Link : 'div'"
        :href="href"
        class="bg-card flex flex-col gap-1.5 rounded-xl border p-4"
        :class="{ 'hover:border-foreground/25 transition-colors': href }"
    >
        <span class="text-muted-foreground text-xs font-medium">{{ label }}</span>

        <span class="text-2xl leading-tight font-semibold tabular-nums" :class="classes_tonalite[tonalite]">
            {{ valeur }}<span v-if="unite" class="ml-0.5 text-base font-medium">{{ unite }}</span>
        </span>

        <span class="flex flex-wrap items-center gap-x-2 gap-y-0.5 text-xs">
            <span v-if="evolution !== null" class="inline-flex items-center gap-0.5 font-medium tabular-nums"
                  :class="couleur_evolution">
                <component :is="icone_evolution" class="size-3.5"/>
                {{ texte_evolution }}
            </span>
            <span v-if="detail" class="text-muted-foreground">{{ detail }}</span>
        </span>
    </component>
</template>


<script setup lang="ts">
import {computed} from 'vue';
import {Link} from '@inertiajs/vue3';
import {ArrowDownRight, ArrowUpRight, Minus} from '@lucide/vue';
import {formater_nombre} from '@/_core/detail/taux';
import type {Tonalite} from '@/_core/renders/statuts';

interface CarteIndicateurInterface {
    label: string;
    valeur: string | number;
    unite?: string;
    detail?: string;
    tonalite?: Tonalite;
    href?: string;

    //==========================================================================================================
    // Comparaison : la valeur actuelle et la valeur précédente (en "pt" pour un taux, en % sinon)
    //==========================================================================================================
    actuel?: number | null;
    precedent?: number | null;
    en_points?: boolean;
    hausse_favorable?: boolean;
}

const props = withDefaults(defineProps<CarteIndicateurInterface>(), {
    unite: undefined,
    detail: undefined,
    tonalite: 'neutre',
    href: undefined,
    actuel: null,
    precedent: null,
    en_points: false,
    hausse_favorable: false,
});

const classes_tonalite: Record<Tonalite, string> = {
    present: 'text-present',
    absent: 'text-absent',
    attente: 'text-attente',
    justifie: 'text-justifie',
    neutre: 'text-foreground',
};

//==============================================================================================================
// Écart avec la période précédente : en points (taux) ou en % (nombres)
//==============================================================================================================
const evolution = computed<number | null>(() => {
    if (props.actuel === null || props.precedent === null) return null;
    if (props.en_points) return Math.round((props.actuel - props.precedent) * 10) / 10;
    if (props.precedent === 0) return props.actuel === 0 ? 0 : null;

    return Math.round(((props.actuel - props.precedent) / props.precedent) * 100);
});

const texte_evolution = computed(() => {
    const ecart = evolution.value ?? 0;
    const signe = ecart > 0 ? '+' : ecart < 0 ? '−' : '';

    return `${signe}${formater_nombre(Math.abs(ecart))}${props.en_points ? ' pt' : ' %'}`;
});

const icone_evolution = computed(() => {
    if (!evolution.value) return Minus;

    return evolution.value > 0 ? ArrowUpRight : ArrowDownRight;
});

//==============================================================================================================
// Plus d'absences = mauvais signe (rouge) ; moins = bon signe (vert), sauf si la hausse est favorable
//==============================================================================================================
const couleur_evolution = computed(() => {
    if (!evolution.value) return 'text-muted-foreground';

    const favorable = props.hausse_favorable ? evolution.value > 0 : evolution.value < 0;

    return favorable ? 'text-present' : 'text-absent';
});
</script>
