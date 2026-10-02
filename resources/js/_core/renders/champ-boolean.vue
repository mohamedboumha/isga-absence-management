<template>
    <!--=========================================================================================================-->
    <!-- Mode list : pastille de statut -->
    <!--=========================================================================================================-->
    <StatutPill v-if="mode_vue === renders.mode_list" :statut="valeur ? 'actif' : 'desactive'" :label="valeur_render"/>

    <!--=========================================================================================================-->
    <!-- Modes detail : la même case, désactivée en consultation -->
    <!--=========================================================================================================-->
    <div v-else class="grid gap-1.5">
        <label :for="nom_champ" class="flex min-h-11 items-center gap-2.5 text-sm"
               :class="is_editable ? 'cursor-pointer' : 'cursor-default'">
            <input
                :id="nom_champ"
                v-model="valeur"
                :name="nom_champ"
                type="checkbox"
                :disabled="!is_editable"
                class="champ-case"
            />
            {{ label }}
        </label>

        <InputError :message="error"/>
    </div>
</template>


<script setup lang="ts">
import {computed} from 'vue';
import InputError from '@/components/InputError.vue';
import {renders} from '@/_core/renders';
import StatutPill from './statut-pill.vue';
import type {ChampProps} from './types';

interface ChampBooleanInterface extends ChampProps {
    label_oui?: string;
    label_non?: string;
}

const props = defineProps<ChampBooleanInterface>();
const valeur = defineModel<boolean>('valeur');

const is_editable = computed(() => props.mode_vue === renders.mode_create || props.mode_vue === renders.mode_edit);

const valeur_render = computed(() => (valeur.value ? props.label_oui ?? 'Oui' : props.label_non ?? 'Non'));
</script>
