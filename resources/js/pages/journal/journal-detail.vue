<template>
    <Head :title="titre_page"/>

    <div class="flex max-w-3xl flex-col gap-6 p-4">
        <!--=====================================================================================================-->
        <!-- En-tête -->
        <!--=====================================================================================================-->
        <div class="flex flex-col gap-2">
            <div class="flex items-center gap-2">
                <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="classe_action">{{
                        item.action_label
                    }}</span>
                <h1 class="text-xl font-semibold">{{ item.entite }} — {{ item.entite_label }}</h1>
            </div>

            <p class="text-muted-foreground text-sm">
                {{ item.date }} · par <strong>{{ item.auteur }}</strong>
                <template v-if="item.ip"> · IP {{ item.ip }}</template>
            </p>
        </div>

        <!--=====================================================================================================-->
        <!-- Valeurs -->
        <!--=====================================================================================================-->
        <div class="overflow-x-auto rounded-lg border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left">
                <tr>
                    <th class="px-4 py-2 font-medium">Champ</th>
                    <th v-if="affiche_ancienne" class="px-4 py-2 font-medium">Ancienne valeur</th>
                    <th v-if="affiche_nouvelle" class="px-4 py-2 font-medium">Nouvelle valeur</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="changement in item.changements" :key="changement.champ" class="border-t">
                    <td class="px-4 py-2 font-mono text-xs">{{ changement.champ }}</td>
                    <td v-if="affiche_ancienne" class="px-4 py-2 text-red-700 dark:text-red-400">
                        {{ afficher(changement.ancienne) }}
                    </td>
                    <td v-if="affiche_nouvelle" class="px-4 py-2 text-green-700 dark:text-green-400">
                        {{ afficher(changement.nouvelle) }}
                    </td>
                </tr>

                <tr v-if="!item.changements.length">
                    <td colspan="3" class="text-muted-foreground px-4 py-6 text-center">Aucun détail pour cette action
                        (ex. restauration).
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>


<script setup lang="ts">
import {computed} from 'vue';
import {Head} from '@inertiajs/vue3';
import type {ModeVue} from '@/_core/renders';

interface Changement {
    champ: string;
    ancienne: unknown;
    nouvelle: unknown;
}

interface EntreeJournal {
    date: string;
    auteur: string;
    action: string;
    action_label: string;
    entite: string;
    entite_label: string | null;
    ip: string | null;
    changements: Changement[];
}

interface JournalDetailInterface {
    mode_vue: ModeVue;
    titre_page: string;
    item: EntreeJournal;
}

const props = defineProps<JournalDetailInterface>();

//==============================================================================================================
// Création : seules les nouvelles valeurs ; suppression : seules les anciennes ; modification : les deux
//==============================================================================================================
const affiche_ancienne = computed(() => ['MODIFICATION', 'SUPPRESSION'].includes(props.item.action));
const affiche_nouvelle = computed(() => ['MODIFICATION', 'CREATION'].includes(props.item.action));

const classe_action = computed(() => ({
    'bg-green-100 text-green-800': props.item.action === 'CREATION',
    'bg-blue-100 text-blue-800': props.item.action === 'MODIFICATION',
    'bg-red-100 text-red-800': props.item.action === 'SUPPRESSION',
    'bg-amber-100 text-amber-800': props.item.action === 'RESTAURATION',
}));

const afficher = (valeur: unknown) => {
    if (valeur === null || valeur === undefined || valeur === '') return '—';

    return typeof valeur === 'object' ? JSON.stringify(valeur) : String(valeur);
};
</script>
