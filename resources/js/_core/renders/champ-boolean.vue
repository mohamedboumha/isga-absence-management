<script setup lang="ts">
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import { Label } from '@/components/ui/label';
import { renders } from '@/_core/renders';
import type { ChampProps } from './types';

const props = defineProps<
    ChampProps & { label_oui?: string; label_non?: string }
>();
const valeur = defineModel<boolean>('valeur');

const is_editable = computed(
    () =>
        props.mode_vue === renders.mode_create ||
        props.mode_vue === renders.mode_edit,
);
const valeur_render = computed(() =>
    valeur.value ? (props.label_oui ?? 'Oui') : (props.label_non ?? 'Non'),
);
</script>

<template>
    <span
        v-if="mode_vue === renders.mode_list"
        class="rounded-full px-2 py-0.5 text-xs font-medium"
        :class="
            valeur
                ? 'bg-green-100 text-green-800'
                : 'bg-muted text-muted-foreground'
        "
    >
        {{ valeur_render }}
    </span>

    <div v-else class="grid gap-2">
        <label
            v-if="is_editable"
            :for="nom_champ"
            class="flex items-center gap-2 text-sm font-medium"
        >
            <input
                :id="nom_champ"
                :name="nom_champ"
                type="checkbox"
                v-model="valeur"
                class="size-4 accent-primary"
            />
            {{ label }}
        </label>

        <template v-else>
            <Label>{{ label }}</Label>
            <p class="text-sm">{{ valeur_render }}</p>
        </template>

        <InputError :message="error" />
    </div>
</template>
