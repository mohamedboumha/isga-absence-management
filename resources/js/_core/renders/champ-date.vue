<template>
    <!--=========================================================================================================-->
    <!-- Mode list : 29/09/2026 -->
    <!--=========================================================================================================-->
    <span v-if="mode_vue === renders.mode_list" class="tabular-nums">{{ date_courte(valeur) || '—' }}</span>

    <!--=========================================================================================================-->
    <!-- Modes detail : bouton + calendrier -->
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
                aria-haspopup="dialog"
                :aria-expanded="ouvert"
                :data-ouvert="ouvert"
                @click="basculer"
            >
                <span class="truncate" :class="{ 'champ-vide': !valeur }">{{ date_longue }}</span>
                <CalendarDays v-if="is_editable" class="size-4 shrink-0 opacity-60" />
            </button>

            <input type="hidden" :name="nom_champ" :value="valeur ?? ''" />

            <!--=================================================================================================-->
            <!-- Calendrier -->
            <!--=================================================================================================-->
            <div v-if="ouvert" class="champ-menu right-auto w-[19rem] p-3" role="dialog" :aria-label="`Choisir : ${label ?? nom_champ}`">
                <!-- Dates possibles -->
                <p v-if="date_min || date_max" class="text-muted-foreground bg-champ mb-3 rounded-md px-2.5 py-1.5 text-xs">
                    Dates possibles : {{ texte_limites }}
                </p>

                <!-- Mois -->
                <div class="mb-2 flex items-center justify-between">
                    <button
                        type="button"
                        class="hover:bg-champ flex size-8 items-center justify-center rounded-md disabled:pointer-events-none disabled:opacity-30"
                        aria-label="Mois précédent"
                        :disabled="!mois_precedent_possible"
                        @click="changer_mois(-1)"
                    >
                        <ChevronLeft class="size-4" />
                    </button>
                    <span class="text-sm font-semibold">{{ titre_mois }}</span>
                    <button
                        type="button"
                        class="hover:bg-champ flex size-8 items-center justify-center rounded-md disabled:pointer-events-none disabled:opacity-30"
                        aria-label="Mois suivant"
                        :disabled="!mois_suivant_possible"
                        @click="changer_mois(1)"
                    >
                        <ChevronRight class="size-4" />
                    </button>
                </div>

                <!-- Jours de la semaine (lundi en premier) -->
                <div class="text-muted-foreground mb-1 grid grid-cols-7 text-center text-xs">
                    <span v-for="jour in jours_semaine" :key="jour" class="py-1">{{ jour }}</span>
                </div>

                <!-- Jours du mois -->
                <div class="grid grid-cols-7 gap-0.5">
                    <button
                        v-for="case_jour in cases"
                        :key="case_jour.chaine"
                        type="button"
                        class="flex h-9 items-center justify-center rounded-md text-sm tabular-nums"
                        :class="classes_jour(case_jour)"
                        :disabled="!case_jour.permise"
                        :aria-pressed="case_jour.chaine === valeur"
                        :aria-label="libelle_jour(case_jour.chaine)"
                        @click="choisir(case_jour.chaine)"
                    >
                        {{ case_jour.jour }}
                    </button>
                </div>

                <!-- Raccourcis -->
                <div class="mt-3 flex justify-between border-t pt-3">
                    <button
                        type="button"
                        class="hover:bg-champ rounded-md px-2 py-1 text-sm font-medium disabled:pointer-events-none disabled:opacity-40"
                        :disabled="!est_permise(aujourdhui())"
                        @click="choisir(aujourdhui())"
                    >
                        Aujourd'hui
                    </button>
                    <button v-if="!required && valeur" type="button" class="text-muted-foreground hover:bg-champ rounded-md px-2 py-1 text-sm" @click="effacer">
                        Effacer
                    </button>
                </div>
            </div>
        </div>
    </ChampConteneur>
</template>


<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { CalendarDays, ChevronLeft, ChevronRight } from '@lucide/vue';
import { renders } from '@/_core/renders';
import ChampConteneur from './champ-conteneur.vue';
import type { ChampProps } from './types';
import { useMenuDeroulant } from './use-menu-deroulant';

interface ChampDateInterface extends ChampProps {
    date_min?: string | null;
    date_max?: string | null;
}

interface CaseJour {
    chaine: string;
    jour: number;
    hors_mois: boolean;
    permise: boolean;
}

const props = withDefaults(defineProps<ChampDateInterface>(), {
    date_min: null,
    date_max: null,
});

const valeur = defineModel<string | null>('valeur');

const is_editable = computed(() => props.mode_vue === renders.mode_create || props.mode_vue === renders.mode_edit);

//==============================================================================================================
// Conversions "2026-09-29" <-> Date (en heure locale, sans décalage de fuseau)
//==============================================================================================================
const deux_chiffres = (nombre: number) => String(nombre).padStart(2, '0');

const en_chaine = (date: Date) => `${date.getFullYear()}-${deux_chiffres(date.getMonth() + 1)}-${deux_chiffres(date.getDate())}`;

const depuis_chaine = (chaine: string) => {
    const [annee, mois, jour] = chaine.split('-').map(Number);

    return new Date(annee, mois - 1, jour);
};

const aujourdhui = () => en_chaine(new Date());

const majuscule = (texte: string) => texte.charAt(0).toUpperCase() + texte.slice(1);

const date_courte = (chaine: string | null | undefined) => (chaine ? chaine.split('-').reverse().join('/') : '');

//==============================================================================================================
// Limites : les chaînes "AAAA-MM-JJ" se comparent directement dans l'ordre chronologique
//==============================================================================================================
const est_permise = (chaine: string) => (!props.date_min || chaine >= props.date_min) && (!props.date_max || chaine <= props.date_max);

const texte_limites = computed(() => {
    if (props.date_min && props.date_max) return `du ${date_courte(props.date_min)} au ${date_courte(props.date_max)}`;
    if (props.date_min) return `à partir du ${date_courte(props.date_min)}`;

    return `jusqu'au ${date_courte(props.date_max)}`;
});

//==============================================================================================================
// Affichage : "Mardi 29 septembre 2026" dans le champ
//==============================================================================================================
const format_long = new Intl.DateTimeFormat('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });

const date_longue = computed(() => {
    if (!valeur.value) return is_editable.value ? props.placeholder ?? 'Choisir une date' : '—';

    return majuscule(format_long.format(depuis_chaine(valeur.value)));
});

const libelle_jour = (chaine: string) => format_long.format(depuis_chaine(chaine));

//==============================================================================================================
// Calendrier : s'ouvre sur la date choisie, sinon aujourd'hui s'il est permis, sinon la limite la plus proche
//==============================================================================================================
const conteneur = ref<HTMLElement | null>(null);
const bouton = ref<HTMLButtonElement | null>(null);

const { ouvert, fermer, basculer } = useMenuDeroulant(conteneur);

const mois_affiche = ref(new Date());

const jours_semaine = ['lun.', 'mar.', 'mer.', 'jeu.', 'ven.', 'sam.', 'dim.'];

const date_de_reference = () => {
    if (valeur.value) return valeur.value;

    const today = aujourdhui();

    if (est_permise(today)) return today;
    if (props.date_min && today < props.date_min) return props.date_min;

    return props.date_max ?? today;
};

watch(ouvert, (est_ouvert) => {
    if (!est_ouvert) return;

    const reference = depuis_chaine(date_de_reference());

    mois_affiche.value = new Date(reference.getFullYear(), reference.getMonth(), 1);
});

const titre_mois = computed(() => majuscule(new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' }).format(mois_affiche.value)));

//==============================================================================================================
// Navigation entre les mois : bloquée au-delà des limites
//==============================================================================================================
const premier_du_mois = computed(() => en_chaine(new Date(mois_affiche.value.getFullYear(), mois_affiche.value.getMonth(), 1)));
const dernier_du_mois = computed(() => en_chaine(new Date(mois_affiche.value.getFullYear(), mois_affiche.value.getMonth() + 1, 0)));

const mois_precedent_possible = computed(() => !props.date_min || premier_du_mois.value > props.date_min);
const mois_suivant_possible = computed(() => !props.date_max || dernier_du_mois.value < props.date_max);

const changer_mois = (pas: number) => {
    mois_affiche.value = new Date(mois_affiche.value.getFullYear(), mois_affiche.value.getMonth() + pas, 1);
};

//==============================================================================================================
// 6 semaines de 7 jours, en commençant le lundi
//==============================================================================================================
const cases = computed<CaseJour[]>(() => {
    const annee = mois_affiche.value.getFullYear();
    const mois = mois_affiche.value.getMonth();
    const decalage = (new Date(annee, mois, 1).getDay() + 6) % 7;

    return Array.from({ length: 42 }, (_, index) => {
        const date = new Date(annee, mois, 1 - decalage + index);
        const chaine = en_chaine(date);

        return { chaine, jour: date.getDate(), hors_mois: date.getMonth() !== mois, permise: est_permise(chaine) };
    });
});

const classes_jour = (case_jour: CaseJour) => {
    if (!case_jour.permise) return 'text-muted-foreground/35 cursor-not-allowed line-through';
    if (case_jour.chaine === valeur.value) return 'bg-isga-gris font-semibold text-white';

    return [
        'hover:bg-champ',
        case_jour.hors_mois ? 'text-muted-foreground/50' : '',
        case_jour.chaine === aujourdhui() ? 'ring-isga-gris/50 font-semibold ring-1 ring-inset' : '',
    ];
};

//==============================================================================================================
// Choix
//==============================================================================================================
const fermer_et_revenir = () => {
    fermer();
    bouton.value?.focus();
};

const choisir = (chaine: string) => {
    if (!est_permise(chaine)) return;

    valeur.value = chaine;
    fermer_et_revenir();
};

const effacer = () => {
    valeur.value = null;
    fermer_et_revenir();
};
</script>
