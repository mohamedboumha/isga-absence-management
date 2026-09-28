<template>
    <!--=========================================================================================================-->
    <!-- Mode list : texte tronqué -->
    <!--=========================================================================================================-->
    <span v-if="mode_vue === renders.mode_list" class="line-clamp-1">{{ valeur }}</span>

    <!--=========================================================================================================-->
    <!-- Modes detail : create, edit, consultation -->
    <!--=========================================================================================================-->
    <div v-else class="grid gap-2">
        <label :for="nom_champ" class="text-sm font-medium">
            {{ label }}
            <span v-if="required && is_editable" class="text-destructive">*</span>
        </label>

        <textarea
            v-if="is_editable"
            :id="nom_champ"
            :name="nom_champ"
            v-model="valeur"
            :placeholder="placeholder"
            :rows="rows"
            class="border-input bg-background rounded-md border px-3 py-2 text-sm"
        />

        <p v-else class="text-sm whitespace-pre-line">{{ valeur || '—' }}</p>

        <InputError :message="error"/>
    </div>
</template>


<script setup lang="ts">
import {computed} from 'vue';
import InputError from '@/components/InputError.vue';
import {renders} from '@/_core/renders';
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
