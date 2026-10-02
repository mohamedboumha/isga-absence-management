<template>
    <!--=========================================================================================================-->
    <!-- Mode list : texte tronqué -->
    <!--=========================================================================================================-->
    <span v-if="mode_vue === renders.mode_list" class="line-clamp-1">{{ valeur || '—' }}</span>

    <!--=========================================================================================================-->
    <!-- Modes detail : la même zone de texte, désactivée en consultation -->
    <!--=========================================================================================================-->
    <ChampConteneur v-else :nom_champ="nom_champ" :label="label" :required="required && is_editable" :error="error">
        <textarea
            :id="nom_champ"
            v-model="valeur"
            :name="nom_champ"
            :rows="rows"
            :placeholder="is_editable ? placeholder : '—'"
            :required="required && is_editable"
            :disabled="!is_editable"
            :aria-invalid="Boolean(error)"
            class="champ"
        />
    </ChampConteneur>
</template>


<script setup lang="ts">
import {computed} from 'vue';
import {renders} from '@/_core/renders';
import ChampConteneur from './champ-conteneur.vue';
import type {ChampProps} from './types';

interface ChampTexteInterface extends ChampProps {
    rows?: number;
}

const props = withDefaults(defineProps<ChampTexteInterface>(), {
    rows: 4,
});

const valeur = defineModel<string | null>('valeur');

const is_editable = computed(() => props.mode_vue === renders.mode_create || props.mode_vue === renders.mode_edit);
</script>
