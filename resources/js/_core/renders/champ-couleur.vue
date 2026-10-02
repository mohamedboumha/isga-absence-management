<template>
    <!--=========================================================================================================-->
    <!-- Mode list : pastille + nom -->
    <!--=========================================================================================================-->
    <span v-if="mode_vue === renders.mode_list" class="inline-flex items-center gap-2">
        <span class="size-3 rounded-full" :style="{ backgroundColor: couleur_affichee ?? 'transparent' }"/>
        {{ nom_affiche }}
    </span>

    <!--=========================================================================================================-->
    <!-- Modes detail : champ + grille de couleurs -->
    <!--=========================================================================================================-->
    <ChampConteneur v-else :nom_champ="nom_champ" :label="label" :required="required && is_editable" :error="error">
        <div ref="conteneur" class="relative" @keydown.esc.prevent="fermer_et_revenir">
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
                <span class="flex min-w-0 items-center gap-2.5">
                    <span
                        class="size-5 shrink-0 rounded-md ring-1 ring-black/10"
                        :style="{ backgroundColor: couleur_affichee ?? 'transparent' }"
                    />
                    <span class="truncate" :class="{ 'champ-vide': !couleur_affichee }">{{ nom_affiche }}</span>
                </span>
                <ChevronDown v-if="is_editable" class="size-4 shrink-0 opacity-60 transition-transform"
                             :class="{ 'rotate-180': ouvert }"/>
            </button>

            <input type="hidden" :name="nom_champ" :value="valeur ?? ''"/>

            <!--=================================================================================================-->
            <!-- Palette -->
            <!--=================================================================================================-->
            <div v-if="ouvert" class="champ-menu right-auto w-[20rem] p-3">
                <!-- Hériter (ex. couleur du cycle) -->
                <button
                    v-if="couleur_heritee !== undefined"
                    type="button"
                    class="hover:bg-champ mb-3 flex w-full items-center gap-2.5 rounded-md px-2 py-1.5 text-sm"
                    :class="{ 'bg-champ font-semibold': !valeur }"
                    @click="choisir(null)"
                >
                    <span class="size-5 rounded-md ring-1 ring-black/10"
                          :style="{ backgroundColor: couleur_heritee ?? 'transparent' }"/>
                    {{ label_heritee }}
                    <Check v-if="!valeur" class="ml-auto size-4"/>
                </button>

                <div class="grid grid-cols-8 gap-2" role="listbox">
                    <button
                        v-for="couleur in palette"
                        :key="couleur.valeur"
                        type="button"
                        role="option"
                        :aria-selected="couleur.valeur === valeur"
                        :aria-label="couleur.label"
                        :title="couleur.label"
                        class="focus-visible:ring-isga-gris flex size-8 items-center justify-center rounded-md text-white ring-offset-2 transition-transform hover:scale-110 focus-visible:ring-2 focus-visible:outline-none"
                        :class="{ 'ring-isga-gris ring-2': couleur.valeur === valeur }"
                        :style="{ backgroundColor: couleur.valeur }"
                        @click="choisir(couleur.valeur)"
                    >
                        <Check v-if="couleur.valeur === valeur" class="size-4"/>
                    </button>
                </div>
            </div>
        </div>
    </ChampConteneur>
</template>


<script setup lang="ts">
import {computed, ref} from 'vue';
import {usePage} from '@inertiajs/vue3';
import {Check, ChevronDown} from '@lucide/vue';
import {renders} from '@/_core/renders';
import ChampConteneur from './champ-conteneur.vue';
import type {ChampProps, SelectOption} from './types';
import {useMenuDeroulant} from './use-menu-deroulant';

interface ChampCouleurInterface extends ChampProps {
    couleur_heritee?: string | null;
    label_heritee?: string;
}

const props = withDefaults(defineProps<ChampCouleurInterface>(), {
    couleur_heritee: undefined,
    label_heritee: 'Couleur héritée',
});

const valeur = defineModel<string | null>('valeur');

const is_editable = computed(() => props.mode_vue === renders.mode_create || props.mode_vue === renders.mode_edit);

//==============================================================================================================
// La palette vient du serveur (CouleurService), partagée avec toutes les pages
//==============================================================================================================
const page = usePage();
const palette = computed(() => (page.props.palette_couleurs as SelectOption[] | undefined) ?? []);

//==============================================================================================================
// Couleur affichée : la sienne, sinon celle dont elle hérite
//==============================================================================================================
const couleur_affichee = computed(() => valeur.value || props.couleur_heritee || null);

const nom_affiche = computed(() => {
    if (valeur.value) return palette.value.find((couleur) => couleur.valeur === valeur.value)?.label ?? valeur.value;
    if (props.couleur_heritee) return props.label_heritee;

    return is_editable.value ? props.placeholder ?? 'Choisir une couleur' : '—';
});

//==============================================================================================================
// Ouverture et choix
//==============================================================================================================
const conteneur = ref<HTMLElement | null>(null);
const bouton = ref<HTMLButtonElement | null>(null);

const {ouvert, fermer, basculer} = useMenuDeroulant(conteneur);

const fermer_et_revenir = () => {
    fermer();
    bouton.value?.focus();
};

const choisir = (couleur: string | null) => {
    valeur.value = couleur;
    fermer_et_revenir();
};
</script>
