<template>
    <!--=========================================================================================================-->
    <!-- Mode list -->
    <!--=========================================================================================================-->
    <span v-if="mode_vue === renders.mode_list" class="tabular-nums">{{ valeur_render }}</span>

    <!--=========================================================================================================-->
    <!-- Modes detail : le même champ, désactivé en consultation -->
    <!--=========================================================================================================-->
    <ChampConteneur v-else :nom_champ="nom_champ" :label="label" :required="required && is_editable" :error="error">
        <div class="relative">
            <input
                :id="nom_champ"
                v-model.number="valeur"
                :name="nom_champ"
                type="number"
                inputmode="numeric"
                :min="min"
                :max="max"
                :placeholder="is_editable ? placeholder : '—'"
                :required="required && is_editable"
                :disabled="!is_editable"
                :aria-invalid="Boolean(error)"
                class="champ tabular-nums"
                :class="{ 'pr-12': suffixe }"
            />

            <span v-if="suffixe"
                  class="text-muted-foreground pointer-events-none absolute inset-y-0 right-0 flex w-12 items-center justify-center text-sm">
                {{ suffixe }}
            </span>
        </div>
    </ChampConteneur>
</template>


<script setup lang="ts">
import {computed} from 'vue';
import {renders} from '@/_core/renders';
import ChampConteneur from './champ-conteneur.vue';
import type {ChampProps} from './types';

interface ChampNombreInterface extends ChampProps {
    min?: number;
    max?: number;
    suffixe?: string;
}

const props = defineProps<ChampNombreInterface>();
const valeur = defineModel<number | null>('valeur');

const is_editable = computed(() => props.mode_vue === renders.mode_create || props.mode_vue === renders.mode_edit);

//==============================================================================================================
// 30  =>  "30 h"
//==============================================================================================================
const valeur_render = computed(() => {
    if (valeur.value === null || valeur.value === undefined) return '—';

    return props.suffixe ? `${valeur.value} ${props.suffixe}` : String(valeur.value);
});
</script>
