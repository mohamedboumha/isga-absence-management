<template>
    <span v-if="mode_vue === renders.mode_list" class="tabular-nums">{{ valeur || '—' }}</span>

    <div v-else class="grid gap-2">
        <label :for="nom_champ" class="text-sm font-medium">
            {{ label }}
            <span v-if="required && is_editable" class="text-destructive">*</span>
        </label>

        <Input v-if="is_editable" :id="nom_champ" :name="nom_champ" type="time" v-model="valeur"/>
        <p v-else class="text-sm tabular-nums">{{ valeur || '—' }}</p>

        <InputError :message="error"/>
    </div>
</template>


<script setup lang="ts">
import {computed} from 'vue';
import InputError from '@/components/InputError.vue';
import {Input} from '@/components/ui/input';
import {renders} from '@/_core/renders';
import type {ChampProps} from './types';

interface ChampHeureInterface extends ChampProps {
}

const props = defineProps<ChampHeureInterface>();
const valeur = defineModel<string | null>('valeur');

const is_editable = computed(() => props.mode_vue === renders.mode_create || props.mode_vue === renders.mode_edit);
</script>
