<template>
    <!--=========================================================================================================-->
    <!-- Mode list -->
    <!--=========================================================================================================-->
    <span v-if="mode_vue === renders.mode_list">{{ nom_fichier || '—' }}</span>

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

        <!-- Document actuel -->
        <a
            v-if="url_fichier"
            :href="url_fichier"
            target="_blank"
            rel="noopener"
            class="text-sm text-primary underline underline-offset-4"
        >
            {{ nom_fichier || 'Voir le document' }}
        </a>
        <p v-else-if="!is_editable" class="text-sm">—</p>

        <!-- Choix d'un (nouveau) fichier -->
        <template v-if="is_editable">
            <input
                :id="nom_champ"
                :name="nom_champ"
                type="file"
                :accept="accept"
                class="text-sm file:mr-3 file:rounded-md file:border-0 file:bg-muted file:px-3 file:py-1.5"
                @change="choisir"
            />
            <p class="text-xs text-muted-foreground">
                {{
                    url_fichier
                        ? 'Choisir un fichier pour remplacer le document actuel. '
                        : ''
                }}{{ aide }}
            </p>
        </template>

        <InputError :message="error" />
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import { renders } from '@/_core/renders';
import type { ChampProps } from './types';

interface ChampFichierInterface extends ChampProps {
    accept?: string;
    aide?: string;
    url_fichier?: string | null;
    nom_fichier?: string | null;
}

const props = defineProps<ChampFichierInterface>();
const valeur = defineModel<File | null>('valeur');

const is_editable = computed(
    () =>
        props.mode_vue === renders.mode_create ||
        props.mode_vue === renders.mode_edit,
);

const choisir = (event: Event) => {
    valeur.value = (event.target as HTMLInputElement).files?.[0] ?? null;
};
</script>
