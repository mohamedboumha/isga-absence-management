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
</script>

<template>
    <!--=========================================================================================================-->
    <!-- Mode list -->
    <!--=========================================================================================================-->
    <span v-if="mode_vue === renders.mode_list">{{ valeur }}</span>

    <!--=========================================================================================================-->
    <!-- Modes detail : create, edit, consultation -->
    <!--=========================================================================================================-->
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
            v-model="valeur"
            :placeholder="placeholder"
        />
        <p v-else class="text-sm">{{ valeur || '—' }}</p>

        <InputError :message="error" />
    </div>
</template>
