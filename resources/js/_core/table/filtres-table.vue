<template>
    <div ref="conteneur" class="relative" @keydown.esc.prevent="fermer">
        <!--=========================================================================================================-->
        <!-- Bouton : "Filtres" + nombre de filtres actifs -->
        <!--=========================================================================================================-->
        <Button type="button" variant="outline" class="h-10" :aria-expanded="ouvert" @click="basculer">
            <SlidersHorizontal class="size-4"/>
            Filtres
            <span v-if="nb_actifs"
                  class="bg-primary text-primary-foreground ml-0.5 rounded-full px-1.5 text-xs tabular-nums">{{
                    nb_actifs
                }}</span>
        </Button>

        <!--=========================================================================================================-->
        <!-- Panneau : un champ par filtre -->
        <!--=========================================================================================================-->
        <div
            v-if="ouvert"
            class="bg-popover absolute top-[calc(100%+0.375rem)] left-0 z-40 w-[min(26rem,calc(100vw-2rem))] rounded-lg border p-4 shadow-lg"
            role="dialog"
            aria-label="Filtres"
        >
            <div class="grid gap-4">
                <template v-for="filtre in filtres" :key="filtre.nom">
                    <!-- Période : du / au -->
                    <fieldset v-if="filtre.type === 'periode'" class="grid gap-1.5">
                        <legend class="mb-1.5 text-sm font-medium">{{ filtre.label }}</legend>

                        <div class="grid grid-cols-2 gap-3">
                            <ChampDate
                                :mode_vue="renders.mode_edit"
                                :nom_champ="`${filtre.nom}_du`"
                                label="Du"
                                placeholder="Début"
                                v-model:valeur="(brouillon[filtre.nom] as ValeurPeriode).du"
                            />
                            <ChampDate
                                :mode_vue="renders.mode_edit"
                                :nom_champ="`${filtre.nom}_au`"
                                label="Au"
                                placeholder="Fin"
                                v-model:valeur="(brouillon[filtre.nom] as ValeurPeriode).au"
                            />
                        </div>
                    </fieldset>

                    <!-- Liste ou Oui / Non -->
                    <ChampSelect
                        v-else
                        :mode_vue="renders.mode_edit"
                        :nom_champ="`filtre_${filtre.nom}`"
                        :label="filtre.label"
                        :options="[{ valeur: '', label: 'Tous' }, ...filtre.options]"
                        v-model:valeur="brouillon[filtre.nom] as string"
                    />
                </template>
            </div>

            <div class="mt-5 flex justify-between gap-2 border-t pt-4">
                <Button type="button" variant="ghost" @click="reinitialiser">Réinitialiser</Button>
                <Button type="button" @click="appliquer">Appliquer</Button>
            </div>
        </div>
    </div>
</template>


<script setup lang="ts">
import {computed, reactive, ref, watch} from 'vue';
import {SlidersHorizontal} from '@lucide/vue';
import {Button} from '@/components/ui/button';
import ChampDate from '@/_core/renders/champ-date.vue';
import ChampSelect from '@/_core/renders/champ-select.vue';
import {renders} from '@/_core/renders';
import {useMenuDeroulant} from '@/_core/renders/use-menu-deroulant';
import type {TableFiltre, ValeurPeriode} from './types';

interface FiltresTableInterface {
    filtres: TableFiltre[];
}

const props = defineProps<FiltresTableInterface>();

const emit = defineEmits<{
    appliquer: [valeurs: Record<string, string | ValeurPeriode>];
}>();

//==============================================================================================================
// Ouverture du panneau (fermé au clic en dehors ou avec Échap)
//==============================================================================================================
const conteneur = ref<HTMLElement | null>(null);

const {ouvert, fermer, basculer} = useMenuDeroulant(conteneur);

//==============================================================================================================
// Brouillon : modifié dans le panneau, envoyé seulement avec "Appliquer"
//==============================================================================================================
const brouillon = reactive<Record<string, string | ValeurPeriode>>({});

const charger_brouillon = () => {
    props.filtres.forEach((filtre) => {
        brouillon[filtre.nom] =
            filtre.type === 'periode'
                ? {
                    du: (filtre.valeur as ValeurPeriode | null)?.du ?? null,
                    au: (filtre.valeur as ValeurPeriode | null)?.au ?? null
                }
                : ((filtre.valeur as string | null) ?? '');
    });
};

charger_brouillon();
watch(ouvert, (est_ouvert) => est_ouvert && charger_brouillon());

const nb_actifs = computed(() => props.filtres.filter((filtre) => filtre.valeur !== null).length);

//==============================================================================================================
// Appliquer : on n'envoie que les filtres renseignés
//==============================================================================================================
const appliquer = () => {
    const valeurs: Record<string, string | ValeurPeriode> = {};

    props.filtres.forEach((filtre) => {
        const valeur = brouillon[filtre.nom];

        if (filtre.type === 'periode') {
            const periode = valeur as ValeurPeriode;

            if (periode.du || periode.au) valeurs[filtre.nom] = periode;
        } else if (valeur !== '' && valeur !== null && valeur !== undefined) {
            valeurs[filtre.nom] = valeur;
        }
    });

    emit('appliquer', valeurs);
    fermer();
};

const reinitialiser = () => {
    emit('appliquer', {});
    fermer();
};
</script>
