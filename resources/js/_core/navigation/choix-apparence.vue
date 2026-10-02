<template>
    <div class="bg-champ flex rounded-md border p-0.5" role="radiogroup" aria-label="Apparence">
        <button
            v-for="choix in choix_apparence"
            :key="choix.valeur"
            type="button"
            role="radio"
            :aria-checked="appearance === choix.valeur"
            :title="choix.label"
            class="flex flex-1 items-center justify-center gap-1.5 rounded-[calc(var(--radius-md)-2px)] px-2.5 py-1.5 text-sm transition-colors"
            :class="appearance === choix.valeur ? 'bg-primary text-primary-foreground font-medium shadow-xs' : 'text-muted-foreground hover:text-foreground'"
            @click="updateAppearance(choix.valeur)"
        >
            <component :is="choix.icone" class="size-4"/>
            <span v-if="avec_libelles">{{ choix.label }}</span>
        </button>
    </div>
</template>


<script setup lang="ts">
import {Monitor, Moon, Sun} from '@lucide/vue';
import {useAppearance} from '@/composables/useAppearance';

interface ChoixApparenceInterface {
    avec_libelles?: boolean;
}

const props = withDefaults(defineProps<ChoixApparenceInterface>(), {
    avec_libelles: false,
});

const {appearance, updateAppearance} = useAppearance();

const choix_apparence = [
    {valeur: 'light', label: 'Clair', icone: Sun},
    {valeur: 'dark', label: 'Sombre', icone: Moon},
    {valeur: 'system', label: 'Système', icone: Monitor},
] as const;
</script>
