<template>
    <span v-if="mode_vue === renders.mode_list">{{ valeur_render }}</span>

    <div v-else class="grid gap-2">
        <label :for="nom_champ" class="text-sm font-medium">
            {{ label }}
            <span v-if="required && is_editable" class="text-destructive"
                >*</span
            >
        </label>

        <select
            v-if="is_editable"
            :id="nom_champ"
            :name="nom_champ"
            v-model="valeur"
            class="h-9 rounded-md border border-input bg-background px-3 text-sm"
        >
            <option :value="null" disabled>
                {{ placeholder ?? 'Choisir...' }}
            </option>
            <option
                v-for="option in options"
                :key="option.valeur"
                :value="option.valeur"
            >
                {{ option.label }}
            </option>
        </select>

        <p v-else class="text-sm">{{ valeur_render }}</p>

        <InputError :message="error" />
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import { renders } from '@/_core/renders';
import type { ChampProps, SelectOption } from './types';

interface ChampSelectInterface extends ChampProps {
    options: SelectOption[];
}

const props = defineProps<ChampSelectInterface>();
const valeur = defineModel<string | number | null>('valeur');

const is_editable = computed(
    () =>
        props.mode_vue === renders.mode_create ||
        props.mode_vue === renders.mode_edit,
);

//==============================================================================================================
// Label de l'option choisie (list / consultation)
//==============================================================================================================
const valeur_render = computed(
    () =>
        props.options.find((option) => option.valeur == valeur.value)?.label ??
        '—',
);
</script>
