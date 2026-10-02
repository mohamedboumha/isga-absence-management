<template>
    <!--=========================================================================================================-->
    <!-- Mode list -->
    <!--=========================================================================================================-->
    <span v-if="mode_vue === renders.mode_list">{{ valeur || '—' }}</span>

    <!--=========================================================================================================-->
    <!-- Modes detail : create, edit, consultation (le même champ, désactivé en consultation) -->
    <!--=========================================================================================================-->
    <ChampConteneur v-else :nom_champ="nom_champ" :label="label" :required="required && is_editable" :error="error">
        <template v-if="$slots['label-action']" #label-action>
            <slot name="label-action"/>
        </template>

        <div class="relative">
            <input
                :id="nom_champ"
                v-model="valeur"
                v-focus="Boolean(autofocus) && is_editable"
                :name="nom_champ"
                :type="type_effectif"
                :autocomplete="autocomplete"
                :placeholder="is_editable ? placeholder : '—'"
                :required="required && is_editable"
                :disabled="!is_editable"
                :aria-invalid="Boolean(error)"
                class="champ"
                :class="{ 'pr-11': type === 'password' && is_editable }"
            />

            <!-- Afficher / masquer le mot de passe -->
            <button
                v-if="type === 'password' && is_editable"
                type="button"
                class="text-muted-foreground hover:text-foreground absolute inset-y-0 right-0 flex w-11 items-center justify-center"
                :aria-label="mot_de_passe_visible ? 'Masquer le mot de passe' : 'Afficher le mot de passe'"
                @click="mot_de_passe_visible = !mot_de_passe_visible"
            >
                <EyeOff v-if="mot_de_passe_visible" class="size-4"/>
                <Eye v-else class="size-4"/>
            </button>
        </div>
    </ChampConteneur>
</template>


<script setup lang="ts">
import {computed, ref} from 'vue';
import {Eye, EyeOff} from '@lucide/vue';
import {renders} from '@/_core/renders';
import ChampConteneur from './champ-conteneur.vue';
import type {ChampProps} from './types';

interface ChampChaineInterface extends ChampProps {
    type?: 'text' | 'email' | 'password' | 'tel';
    autocomplete?: string;
    autofocus?: boolean;
}

const props = withDefaults(defineProps<ChampChaineInterface>(), {
    type: 'text',
});

const valeur = defineModel<string | null>('valeur');

const is_editable = computed(() => props.mode_vue === renders.mode_create || props.mode_vue === renders.mode_edit);

//==============================================================================================================
// Mot de passe : affiché en clair à la demande
//==============================================================================================================
const mot_de_passe_visible = ref(false);

const type_effectif = computed(() => (props.type === 'password' && mot_de_passe_visible.value ? 'text' : props.type));
</script>
