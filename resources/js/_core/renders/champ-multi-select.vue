<template>
    <!--=========================================================================================================-->
    <!-- Mode list -->
    <!--=========================================================================================================-->
    <span v-if="mode_vue === renders.mode_list">{{
            options_choisies.map((option) => option.label).join(', ') || '—'
        }}</span>

    <!--=========================================================================================================-->
    <!-- Modes detail -->
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
                :data-ouvert="ouvert"
                @click="basculer"
            >
                <span class="truncate" :class="{ 'champ-vide': !options_choisies.length }">{{ resume }}</span>
                <span class="flex shrink-0 items-center gap-2">
                    <span v-if="options_choisies.length"
                          class="bg-background rounded px-1.5 text-xs tabular-nums">{{ options_choisies.length }}</span>
                    <ChevronDown v-if="is_editable" class="size-4 opacity-60 transition-transform"
                                 :class="{ 'rotate-180': ouvert }"/>
                </span>
            </button>

            <!--=================================================================================================-->
            <!-- Panneau : recherche + options à cocher -->
            <!--=================================================================================================-->
            <div v-if="ouvert" class="champ-menu">
                <div class="border-b p-2">
                    <input ref="champ_recherche" v-model="recherche" type="text" class="champ h-9 text-sm"
                           placeholder="Rechercher..."/>
                </div>

                <ul role="listbox" aria-multiselectable="true" class="max-h-64 overflow-y-auto p-1">
                    <li
                        v-for="(option, index) in options_filtrees"
                        :key="String(option.valeur)"
                        role="option"
                        :aria-selected="est_choisie(option)"
                        class="champ-option justify-start"
                        :data-actif="index === index_actif"
                        @mousedown.prevent="basculer_option(option)"
                        @mouseenter="index_actif = index"
                    >
                        <span
                            class="flex size-4 shrink-0 items-center justify-center rounded-[3px] border"
                            :class="est_choisie(option) ? 'border-isga-gris bg-isga-gris text-white' : 'bg-champ'"
                        >
                            <Check v-if="est_choisie(option)" class="size-3"/>
                        </span>
                        <span class="truncate">{{ option.label }}</span>
                    </li>

                    <li v-if="!options_filtrees.length" class="text-muted-foreground px-3 py-2 text-sm">Aucun résultat
                    </li>
                </ul>
            </div>
        </div>

        <!--=====================================================================================================-->
        <!-- Éléments choisis (retirables en saisie) -->
        <!--=====================================================================================================-->
        <div v-if="options_choisies.length" class="flex flex-wrap gap-1.5 pt-1">
            <span
                v-for="option in options_choisies"
                :key="String(option.valeur)"
                class="bg-champ inline-flex items-center gap-1 rounded-md border px-2 py-0.5 text-xs"
            >
                {{ option.label }}
                <button
                    v-if="is_editable"
                    type="button"
                    class="text-muted-foreground hover:text-foreground"
                    :aria-label="`Retirer ${option.label}`"
                    @click="basculer_option(option)"
                >
                    <X class="size-3"/>
                </button>
            </span>
        </div>
    </ChampConteneur>
</template>


<script setup lang="ts">
import {computed, nextTick, ref, watch} from 'vue';
import {Check, ChevronDown, X} from '@lucide/vue';
import {renders} from '@/_core/renders';
import ChampConteneur from './champ-conteneur.vue';
import type {ChampProps, SelectOption} from './types';
import {useMenuDeroulant} from './use-menu-deroulant';

interface ChampMultiSelectInterface extends ChampProps {
    options: SelectOption[];
}

const props = defineProps<ChampMultiSelectInterface>();
const valeur = defineModel<(string | number)[]>('valeur', {default: () => []});

const is_editable = computed(() => props.mode_vue === renders.mode_create || props.mode_vue === renders.mode_edit);

const est_choisie = (option: SelectOption) => (valeur.value ?? []).includes(option.valeur);

const options_choisies = computed(() => props.options.filter((option) => est_choisie(option)));

//==============================================================================================================
// Résumé dans le champ : "GI-BDD, GI-WEB +2"
//==============================================================================================================
const resume = computed(() => {
    const labels = options_choisies.value.map((option) => option.label);

    if (!labels.length) return is_editable.value ? props.placeholder ?? 'Choisir...' : '—';
    if (labels.length <= 2) return labels.join(', ');

    return `${labels.slice(0, 2).join(', ')} +${labels.length - 2}`;
});

//==============================================================================================================
// Ouverture et recherche sans accents
//==============================================================================================================
const conteneur = ref<HTMLElement | null>(null);
const bouton = ref<HTMLButtonElement | null>(null);
const champ_recherche = ref<HTMLInputElement | null>(null);

const {ouvert, ouvrir, fermer, basculer} = useMenuDeroulant(conteneur);

const recherche = ref('');
const index_actif = ref(0);

const normaliser = (texte: string) =>
    texte
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '');

const options_filtrees = computed(() => {
    const terme = normaliser(recherche.value.trim());

    return terme ? props.options.filter((option) => normaliser(option.label).includes(terme)) : props.options;
});

watch(ouvert, async (est_ouvert) => {
    if (!est_ouvert) {
        recherche.value = '';
        return;
    }

    index_actif.value = 0;

    await nextTick();
    champ_recherche.value?.focus();
});

watch(recherche, () => {
    index_actif.value = 0;
});

//==============================================================================================================
// Ajouter / retirer une option (le panneau reste ouvert)
//==============================================================================================================
const basculer_option = (option: SelectOption) => {
    const liste = [...(valeur.value ?? [])];
    const position = liste.indexOf(option.valeur);

    if (position >= 0) {
        liste.splice(position, 1);
    } else {
        liste.push(option.valeur);
    }

    valeur.value = liste;
};

const defiler = () => nextTick(() => conteneur.value?.querySelector('[data-actif="true"]')?.scrollIntoView({block: 'nearest'}));

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

            if (option) basculer_option(option);
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
