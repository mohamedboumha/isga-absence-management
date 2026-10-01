<template>
    <!--=========================================================================================================-->
    <!-- Mode list -->
    <!--=========================================================================================================-->
    <span v-if="mode_vue === renders.mode_list" class="tabular-nums">{{
        valeur_render
    }}</span>

    <!--=========================================================================================================-->
    <!-- Modes detail : create, edit, consultation -->
    <!--=========================================================================================================-->
    <div v-else class="grid gap-2">
        <label :for="nom_champ" class="text-sm font-medium">
            {{ label }}
            <span v-if="required && is_editable" class="text-destructive"
                >*</span
            >
        </label>

        <div v-if="is_editable" class="flex items-center gap-2">
            <Input
                :id="nom_champ"
                :name="nom_champ"
                type="number"
                v-model.number="valeur"
                :min="min"
                :max="max"
                :placeholder="placeholder"
            />
            <span v-if="suffixe" class="text-sm text-muted-foreground">{{
                suffixe
            }}</span>
        </div>

        <p v-else class="text-sm">{{ valeur_render }}</p>

        <InputError :message="error" />
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { renders } from '@/_core/renders';
import type { ChampProps } from './types';

interface ChampNombreInterface extends ChampProps {
    min?: number;
    max?: number;
    suffixe?: string;
}

const props = defineProps<ChampNombreInterface>();
const valeur = defineModel<number | null>('valeur');

const is_editable = computed(
    () =>
        props.mode_vue === renders.mode_create ||
        props.mode_vue === renders.mode_edit,
);

//==============================================================================================================
// 30  =>  "30 h"
//==============================================================================================================
const valeur_render = computed(() => {
    if (valeur.value === null || valeur.value === undefined) return '—';

    return props.suffixe
        ? `${valeur.value} ${props.suffixe}`
        : String(valeur.value);
});
</script>
