<template>
    <div>
        <component
            :is="ligne.url ? Link : 'div'"
            v-for="ligne in lignes_affichees"
            :key="ligne.cle"
            :href="ligne.url ?? undefined"
            class="grid grid-cols-[minmax(0,11rem)_minmax(0,1fr)_4.5rem] items-center gap-3 border-b px-5 py-2.5 text-sm last:border-0"
            :class="{ 'hover:bg-muted/50': ligne.url }"
        >
            <span class="flex min-w-0 flex-col">
                <BadgeCouleur v-if="ligne.couleur" :couleur="ligne.couleur" :label="ligne.label" class="self-start"/>
                <span v-else class="truncate font-medium">{{ ligne.label }}</span>
                <span v-if="ligne.sous_label" class="text-muted-foreground mt-0.5 truncate text-xs">{{
                        ligne.sous_label
                    }}</span>
            </span>

            <span class="bg-champ h-2 overflow-hidden rounded-full" role="presentation">
                <span
                    class="block h-full rounded-full transition-[width] duration-300"
                    :style="{ width: `${largeur(ligne.valeur)}%`, backgroundColor: ligne.couleur ?? 'var(--isga-gris)' }"
                />
            </span>

            <span class="text-right font-semibold tabular-nums" :class="ligne.classe_valeur">{{ ligne.texte }}</span>
        </component>

        <p v-if="!lignes.length" class="text-muted-foreground px-5 py-8 text-center text-sm">{{ vide }}</p>

        <button
            v-if="limite && lignes.length > limite"
            type="button"
            class="text-muted-foreground hover:text-foreground w-full border-t px-5 py-2.5 text-left text-sm font-medium"
            @click="tout_afficher = !tout_afficher"
        >
            {{ tout_afficher ? 'Afficher moins' : `Afficher les ${lignes.length - limite} autres` }}
        </button>
    </div>
</template>


<script setup lang="ts">
import {computed, ref} from 'vue';
import {Link} from '@inertiajs/vue3';
import BadgeCouleur from '@/_core/renders/badge-couleur.vue';

export interface LigneBarre {
    cle: string;
    label: string;
    sous_label?: string | null;
    valeur: number;
    texte: string;
    couleur?: string | null;
    url?: string | null;
    classe_valeur?: string;
}

interface ListeBarresInterface {
    lignes: LigneBarre[];
    vide?: string;
    limite?: number;
}

const props = withDefaults(defineProps<ListeBarresInterface>(), {
    vide: 'Aucune donnée sur cette période.',
    limite: undefined,
});

const tout_afficher = ref(false);

const lignes_affichees = computed(() => (props.limite && !tout_afficher.value ? props.lignes.slice(0, props.limite) : props.lignes));

//==============================================================================================================
// La plus grande valeur remplit la barre ; les autres en proportion
//==============================================================================================================
const maximum = computed(() => Math.max(...props.lignes.map((ligne) => ligne.valeur), 0));

const largeur = (valeur: number) => (maximum.value > 0 ? Math.max((valeur / maximum.value) * 100, valeur > 0 ? 2 : 0) : 0);
</script>
