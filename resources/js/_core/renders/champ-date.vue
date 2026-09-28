<script setup lang="ts">
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { renders } from '@/_core/renders';
import type { ChampProps } from './types';

const props = defineProps<ChampProps>();
const valeur = defineModel<string>('valeur');

const is_editable = computed(
    () =>
        props.mode_vue === renders.mode_create ||
        props.mode_vue === renders.mode_edit,
);

//==============================================================================================================
// "2026-09-01"  =>  "01/09/2026"
//==============================================================================================================
const valeur_render = computed(() => {
    if (!valeur.value) return '—';

    const [annee, mois, jour] = valeur.value.split('-');

    return `${jour}/${mois}/${annee}`;
});
</script>

<template>
    <span v-if="mode_vue === renders.mode_list">{{ valeur_render }}</span>

    <div v-else class="grid gap-2">
        <Label :for="nom_champ">
            {{ label }}
            <span v-if="required && is_editable" class="text-destructive"
                >*</span
            >
        </Label>

        <Input
            v-if="is_editable"
            :id="nom_champ"
            :name="nom_champ"
            type="date"
            v-model="valeur"
        />
        <p v-else class="text-sm">{{ valeur_render }}</p>

        <InputError :message="error" />
    </div>
</template>
