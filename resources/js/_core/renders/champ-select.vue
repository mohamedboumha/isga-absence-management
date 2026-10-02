<template>
    <!--=========================================================================================================-->
    <!-- Mode list -->
    <!--=========================================================================================================-->
    <span v-if="mode_vue === renders.mode_list">{{ option_choisie?.label ?? '—' }}</span>

    <!--=========================================================================================================-->
    <!-- Modes detail : bouton + liste déroulante (désactivé en consultation) -->
    <!--=========================================================================================================-->
    <ChampConteneur v-else :nom_champ="nom_champ" :label="label" :required="required && is_editable" :error="error">
        <div ref="conteneur" class="relative" @keydown="sur_touche">
            <button
                :id="nom_champ"
                ref="bouton"
                type="button"
                class="champ"
                :disabled="!is_editable"
                :aria-invalid="Boolean(error)"
                aria-haspopup="listbox"
                :aria-expanded="ouvert"
                :aria-controls="`${nom_champ}-liste`"
                :data-ouvert="ouvert"
                @click="basculer"
            >
                <span class="truncate" :class="{ 'champ-vide': !option_choisie }">
                    {{ option_choisie?.label ?? (is_editable ? placeholder ?? 'Choisir...' : '—') }}
                </span>
                <ChevronDown v-if="is_editable" class="size-4 shrink-0 opacity-60 transition-transform"
                             :class="{ 'rotate-180': ouvert }"/>
            </button>

            <input type="hidden" :name="nom_champ" :value="valeur ?? ''"/>

            <!--=================================================================================================-->
            <!-- Panneau : recherche (au-delà de 7 options) + options -->
            <!--=================================================================================================-->
            <div v-if="ouvert" class="champ-menu">
                <div v-if="avec_recherche" class="border-b p-2">
                    <input
                        ref="champ_recherche"
                        v-model="recherche"
                        type="text"
                        class="champ h-9 text-sm"
                        placeholder="Rechercher..."
                        :aria-controls="`${nom_champ}-liste`"
                    />
                </div>

                <ul :id="`${nom_champ}-liste`" role="listbox" class="max-h-64 overflow-y-auto p-1">
                    <li
                        v-for="(option, index) in options_filtrees"
                        :id="`${nom_champ}-option-${index}`"
                        :key="String(option.valeur)"
                        role="option"
                        :aria-selected="option.valeur == valeur"
                        class="champ-option"
                        :data-actif="index === index_actif"
                        :data-choisi="option.valeur == valeur"
                        @mousedown.prevent="choisir(option)"
                        @mouseenter="index_actif = index"
                    >
                        <span class="truncate">{{ option.label }}</span>
                        <Check v-if="option.valeur == valeur" class="size-4 shrink-0"/>
                    </li>

                    <li v-if="!options_filtrees.length" class="text-muted-foreground px-3 py-2 text-sm">Aucun résultat
                    </li>
                </ul>
            </div>
        </div>
    </ChampConteneur>
</template>


<script setup lang="ts">
import {computed, nextTick, ref, watch} from 'vue';
import {Check, ChevronDown} from '@lucide/vue';
import {renders} from '@/_core/renders';
import ChampConteneur from './champ-conteneur.vue';
import type {ChampProps, SelectOption} from './types';
import {useMenuDeroulant} from './use-menu-deroulant';

interface ChampSelectInterface extends ChampProps {
    options: SelectOption[];
}

const props = defineProps<ChampSelectInterface>();
const valeur = defineModel<string | number | null>('valeur');

const is_editable = computed(() => props.mode_vue === renders.mode_create || props.mode_vue === renders.mode_edit);

const option_choisie = computed(() => props.options.find((option) => option.valeur == valeur.value) ?? null);

//==============================================================================================================
// Ouverture du panneau
//==============================================================================================================
const conteneur = ref<HTMLElement | null>(null);
const bouton = ref<HTMLButtonElement | null>(null);
const champ_recherche = ref<HTMLInputElement | null>(null);

const {ouvert, ouvrir, fermer, basculer} = useMenuDeroulant(conteneur);

//==============================================================================================================
// Recherche sans accents : "fil" trouve "Filière"
//==============================================================================================================
const recherche = ref('');
const index_actif = ref(0);

const avec_recherche = computed(() => props.options.length > 7);

const normaliser = (texte: string) =>
    texte
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '');

const options_filtrees = computed(() => {
    const terme = normaliser(recherche.value.trim());

    return terme ? props.options.filter((option) => normaliser(option.label).includes(terme)) : props.options;
});

const defiler = () => nextTick(() => conteneur.value?.querySelector('[data-actif="true"]')?.scrollIntoView({block: 'nearest'}));

watch(ouvert, async (est_ouvert) => {
    if (!est_ouvert) {
        recherche.value = '';
        return;
    }

    index_actif.value = Math.max(0, options_filtrees.value.findIndex((option) => option.valeur == valeur.value));

    await nextTick();

    if (avec_recherche.value) {
        champ_recherche.value?.focus();
    }

    defiler();
});

watch(recherche, () => {
    index_actif.value = 0;
});

//==============================================================================================================
// Choix et clavier (↑ ↓ Entrée Échap)
//==============================================================================================================
const choisir = (option: SelectOption) => {
    valeur.value = option.valeur;
    fermer();
    bouton.value?.focus();
};

const sur_touche = (event: KeyboardEvent) => {
    if (!is_editable.value) return;

    if (!ouvert.value) {
        if (event.target === bouton.value && ['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(event.key)) {
            event.preventDefault();
            ouvrir();
        }

        return;
    }

    switch (event.key) {
        case 'ArrowDown':
            event.preventDefault();
            index_actif.value = Math.min(index_actif.value + 1, options_filtrees.value.length - 1);
            defiler();
            break;

        case 'ArrowUp':
            event.preventDefault();
            index_actif.value = Math.max(index_actif.value - 1, 0);
            defiler();
            break;

        case 'Enter': {
            event.preventDefault();
            const option = options_filtrees.value[index_actif.value];

            if (option) choisir(option);
            break;
        }

        case 'Escape':
            event.preventDefault();
            fermer();
            bouton.value?.focus();
            break;

        case 'Tab':
            fermer();
            break;
    }
};
</script>
