<template>
    <!--=========================================================================================================-->
    <!-- Mode list -->
    <!--=========================================================================================================-->
    <span v-if="mode_vue === renders.mode_list" class="tabular-nums">{{ valeur || '—' }}</span>

    <!--=========================================================================================================-->
    <!-- Modes detail : bouton + liste de créneaux -->
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
                <span class="tabular-nums" :class="{ 'champ-vide': !valeur }">{{
                        valeur || (is_editable ? '--:--' : '—')
                    }}</span>
                <Clock v-if="is_editable" class="size-4 shrink-0 opacity-60"/>
            </button>

            <input type="hidden" :name="nom_champ" :value="valeur ?? ''"/>

            <ul v-if="ouvert" role="listbox" class="champ-menu max-h-56 overflow-y-auto p-1">
                <li
                    v-for="creneau in creneaux"
                    :key="creneau"
                    role="option"
                    :aria-selected="creneau === valeur"
                    class="champ-option tabular-nums"
                    :data-actif="creneau === valeur"
                    :data-choisi="creneau === valeur"
                    @mousedown.prevent="choisir(creneau)"
                >
                    {{ creneau }}
                    <Check v-if="creneau === valeur" class="size-4 shrink-0"/>
                </li>
            </ul>
        </div>
    </ChampConteneur>
</template>


<script setup lang="ts">
import {computed, nextTick, ref, watch} from 'vue';
import {Check, Clock} from '@lucide/vue';
import {renders} from '@/_core/renders';
import ChampConteneur from './champ-conteneur.vue';
import type {ChampProps} from './types';
import {useMenuDeroulant} from './use-menu-deroulant';

interface ChampHeureInterface extends ChampProps {
    pas_minutes?: number;
    heure_min?: string;
    heure_max?: string;
}

const props = withDefaults(defineProps<ChampHeureInterface>(), {
    pas_minutes: 15,
    heure_min: '07:00',
    heure_max: '21:00',
});

const valeur = defineModel<string | null>('valeur');

const is_editable = computed(() => props.mode_vue === renders.mode_create || props.mode_vue === renders.mode_edit);

//==============================================================================================================
// Créneaux : de heure_min à heure_max, tous les pas_minutes (07:00, 07:15, 07:30...)
//==============================================================================================================
const en_minutes = (heure: string) => {
    const [h, m] = heure.split(':').map(Number);

    return h * 60 + m;
};

const creneaux = computed(() => {
    const liste: string[] = [];

    for (let minutes = en_minutes(props.heure_min); minutes <= en_minutes(props.heure_max); minutes += props.pas_minutes) {
        liste.push(`${String(Math.floor(minutes / 60)).padStart(2, '0')}:${String(minutes % 60).padStart(2, '0')}`);
    }

    //==========================================================================================================
    // Une heure enregistrée hors de la grille reste proposée
    //==========================================================================================================
    if (valeur.value && !liste.includes(valeur.value)) {
        liste.push(valeur.value);
        liste.sort();
    }

    return liste;
});

//==============================================================================================================
// Ouverture : la liste s'ouvre sur l'heure choisie
//==============================================================================================================
const conteneur = ref<HTMLElement | null>(null);
const bouton = ref<HTMLButtonElement | null>(null);

const {ouvert, fermer, basculer} = useMenuDeroulant(conteneur);

watch(ouvert, async (est_ouvert) => {
    if (!est_ouvert) return;

    await nextTick();
    conteneur.value?.querySelector('[data-choisi="true"]')?.scrollIntoView({block: 'center'});
});

const fermer_et_revenir = () => {
    fermer();
    bouton.value?.focus();
};

const choisir = (creneau: string) => {
    valeur.value = creneau;
    fermer_et_revenir();
};
</script>
