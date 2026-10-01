<template>
    <!--=========================================================================================================-->
    <!-- Mode list -->
    <!--=========================================================================================================-->
    <span v-if="mode_vue === renders.mode_list">{{
        labels_choisis.join(', ') || '—'
    }}</span>

    <!--=========================================================================================================-->
    <!-- Modes detail : create, edit, consultation -->
    <!--=========================================================================================================-->
    <div v-else class="grid gap-2">
        <label class="text-sm font-medium">
            {{ label }}
            <span v-if="required && is_editable" class="text-destructive"
                >*</span
            >
            <span v-if="is_editable" class="font-normal text-muted-foreground">
                ({{ valeur?.length ?? 0 }} choisi(s))</span
            >
        </label>

        <!-- Édition : recherche + cases à cocher -->
        <div v-if="is_editable" class="rounded-md border">
            <input
                v-model="recherche"
                type="text"
                placeholder="Filtrer..."
                class="w-full border-b bg-transparent px-3 py-2 text-sm outline-none"
            />

            <div class="max-h-60 overflow-y-auto p-2">
                <label
                    v-for="option in options_filtrees"
                    :key="option.valeur"
                    class="flex cursor-pointer items-center gap-2 rounded px-2 py-1 text-sm hover:bg-muted/50"
                >
                    <input
                        type="checkbox"
                        :value="option.valeur"
                        v-model="valeur"
                        class="size-4 accent-primary"
                    />
                    {{ option.label }}
                </label>

                <p
                    v-if="!options_filtrees.length"
                    class="px-2 py-1 text-sm text-muted-foreground"
                >
                    Aucun résultat
                </p>
            </div>
        </div>

        <!-- Consultation : badges -->
        <div v-else class="flex flex-wrap gap-2">
            <span
                v-for="label_choisi in labels_choisis"
                :key="label_choisi"
                class="rounded-full bg-muted px-2 py-0.5 text-xs"
            >
                {{ label_choisi }}
            </span>
            <p v-if="!labels_choisis.length" class="text-sm">—</p>
        </div>

        <InputError :message="error" />
    </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { renders } from '@/_core/renders';
import type { ChampProps, SelectOption } from './types';

interface ChampMultiSelectInterface extends ChampProps {
    options: SelectOption[];
}

const props = defineProps<ChampMultiSelectInterface>();
const valeur = defineModel<(string | number)[]>('valeur', {
    default: () => [],
});

const is_editable = computed(
    () =>
        props.mode_vue === renders.mode_create ||
        props.mode_vue === renders.mode_edit,
);

//==============================================================================================================
// Filtre de la liste des options
//==============================================================================================================
const recherche = ref('');

const options_filtrees = computed(() =>
    props.options.filter((option) =>
        option.label.toLowerCase().includes(recherche.value.toLowerCase()),
    ),
);

//==============================================================================================================
// Labels des options choisies (list / consultation)
//==============================================================================================================
const labels_choisis = computed(() =>
    props.options
        .filter((option) => valeur.value?.includes(option.valeur))
        .map((option) => option.label),
);
</script>
