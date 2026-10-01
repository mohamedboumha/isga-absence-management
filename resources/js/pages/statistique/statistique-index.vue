<template>
    <Head :title="titre_page" />

    <div class="flex flex-col gap-6 p-4">
        <h1 class="text-xl font-semibold">{{ titre_page }}</h1>

        <!--=====================================================================================================-->
        <!-- Filtres -->
        <!--=====================================================================================================-->
        <form
            class="grid items-end gap-4 rounded-xl border p-4 sm:grid-cols-2 lg:grid-cols-7"
            @submit.prevent="appliquer"
        >
            <ChampDate
                :mode_vue="renders.mode_edit"
                nom_champ="date_debut"
                label="Du"
                v-model:valeur="form.date_debut"
            />
            <ChampDate
                :mode_vue="renders.mode_edit"
                nom_champ="date_fin"
                label="Au"
                v-model:valeur="form.date_fin"
            />

            <ChampSelect
                :mode_vue="renders.mode_edit"
                nom_champ="cycle_id"
                label="Cycle"
                :options="selects.cycles"
                v-model:valeur="form.cycle_id"
            />
            <ChampSelect
                :mode_vue="renders.mode_edit"
                nom_champ="niveau_etude_id"
                label="Niveau"
                :options="selects.niveaux"
                v-model:valeur="form.niveau_etude_id"
            />

            <ChampSelect
                :mode_vue="renders.mode_edit"
                nom_champ="groupe_id"
                label="Groupe"
                :options="selects.groupes"
                v-model:valeur="form.groupe_id"
            />
            <ChampSelect
                :mode_vue="renders.mode_edit"
                nom_champ="module_id"
                label="Module"
                :options="selects.modules"
                v-model:valeur="form.module_id"
            />

            <div class="flex gap-2">
                <Button type="submit">Appliquer</Button>
                <Button as-child variant="outline">
                    <Link href="/statistiques">Réinitialiser</Link>
                </Button>
                <Button as-child variant="outline"
                    ><a :href="url_export">Excel</a></Button
                >
            </div>
        </form>

        <!--=====================================================================================================-->
        <!-- Indicateurs -->
        <!--=====================================================================================================-->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <CarteKpi
                titre="Taux d'absence"
                :valeur="`${resume.taux} %`"
                sous_titre="Heures d'absence / heures de séances"
            />
            <CarteKpi
                titre="Séances tenues"
                :valeur="resume.nb_seances"
                sous_titre="Non annulées, appel fait"
            />
            <CarteKpi
                titre="Absences"
                :valeur="resume.nb_absences"
                :sous_titre="`${resume.heures_absence} h au total`"
            />
            <CarteKpi
                titre="Justifiées"
                :valeur="resume.nb_justifiees"
                tonalite="succes"
            />
            <CarteKpi
                titre="Non justifiées"
                :valeur="resume.nb_non_justifiees"
                :tonalite="resume.nb_non_justifiees ? 'alerte' : 'neutre'"
            />
        </div>

        <!--=====================================================================================================-->
        <!-- Graphiques -->
        <!--=====================================================================================================-->
        <div class="grid gap-4 lg:grid-cols-2">
            <div class="rounded-xl border p-4">
                <h2 class="mb-3 font-medium">Taux d'absence par groupe</h2>
                <GraphiqueBarres
                    :labels="par_groupe.map((ligne) => ligne.nom)"
                    :valeurs="par_groupe.map((ligne) => ligne.taux)"
                    libelle="Taux d'absence"
                    suffixe=" %"
                />
            </div>

            <div class="rounded-xl border p-4">
                <h2 class="mb-3 font-medium">Absences par semaine</h2>
                <GraphiqueLigne
                    :labels="evolution.labels"
                    :valeurs="evolution.valeurs"
                    libelle="Absences"
                />
            </div>
        </div>

        <!--=====================================================================================================-->
        <!-- Classements -->
        <!--=====================================================================================================-->
        <div class="grid gap-4 lg:grid-cols-2">
            <div class="overflow-x-auto rounded-xl border">
                <h2 class="border-b p-4 font-medium">
                    Étudiants les plus absents
                </h2>
                <table class="w-full text-sm">
                    <thead class="bg-muted/50 text-left">
                        <tr>
                            <th class="px-4 py-2 font-medium">Étudiant</th>
                            <th class="px-4 py-2 font-medium">Groupe</th>
                            <th class="px-4 py-2 text-right font-medium">
                                Absences
                            </th>
                            <th class="px-4 py-2 text-right font-medium">
                                Non just.
                            </th>
                            <th class="px-4 py-2 text-right font-medium">
                                Taux
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="etudiant in top_etudiants"
                            :key="etudiant.cle"
                            class="border-t"
                        >
                            <td class="px-4 py-2">
                                <Link
                                    :href="`/etudiant/${etudiant.cle}`"
                                    class="hover:underline"
                                    >{{ etudiant.nom_complet }}
                                </Link>
                                <p class="text-xs text-muted-foreground">
                                    {{ etudiant.cne }}
                                </p>
                            </td>
                            <td class="px-4 py-2">{{ etudiant.groupe }}</td>
                            <td class="px-4 py-2 text-right tabular-nums">
                                {{ etudiant.nb_absences }}
                            </td>
                            <td class="px-4 py-2 text-right tabular-nums">
                                {{ etudiant.nb_non_justifiees }}
                            </td>
                            <td
                                class="px-4 py-2 text-right font-medium tabular-nums"
                            >
                                {{ etudiant.taux }} %
                            </td>
                        </tr>
                        <tr v-if="!top_etudiants.length">
                            <td
                                colspan="5"
                                class="px-4 py-6 text-center text-muted-foreground"
                            >
                                Aucune absence sur cette période.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="overflow-x-auto rounded-xl border">
                <h2 class="border-b p-4 font-medium">Par module</h2>
                <table class="w-full text-sm">
                    <thead class="bg-muted/50 text-left">
                        <tr>
                            <th class="px-4 py-2 font-medium">Module</th>
                            <th class="px-4 py-2 text-right font-medium">
                                Séances
                            </th>
                            <th class="px-4 py-2 text-right font-medium">
                                Absences
                            </th>
                            <th class="px-4 py-2 text-right font-medium">
                                Taux
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(ligne, index) in par_module"
                            :key="index"
                            class="border-t"
                        >
                            <td class="px-4 py-2">{{ ligne.module }}</td>
                            <td class="px-4 py-2 text-right tabular-nums">
                                {{ ligne.nb_seances }}
                            </td>
                            <td class="px-4 py-2 text-right tabular-nums">
                                {{ ligne.nb_absences }}
                            </td>
                            <td
                                class="px-4 py-2 text-right font-medium tabular-nums"
                            >
                                {{ ligne.taux }} %
                            </td>
                        </tr>
                        <tr v-if="!par_module.length">
                            <td
                                colspan="4"
                                class="px-4 py-6 text-center text-muted-foreground"
                            >
                                Aucune séance tenue sur cette période.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import GraphiqueBarres from '@/_core/charts/graphique-barres.vue';
import GraphiqueLigne from '@/_core/charts/graphique-ligne.vue';
import CarteKpi from '@/_core/dashboard/carte-kpi.vue';
import ChampDate from '@/_core/renders/champ-date.vue';
import ChampSelect from '@/_core/renders/champ-select.vue';
import { renders } from '@/_core/renders';
import type { SelectOption } from '@/_core/renders/types';
import { computed } from 'vue';

interface Filtres {
    date_debut: string;
    date_fin: string;
    cycle_id: number | string | null;
    niveau_etude_id: number | string | null;
    groupe_id: number | string | null;
    module_id: number | string | null;
}

interface StatistiqueIndexInterface {
    titre_page: string;
    filtres: Filtres;
    resume: {
        taux: number;
        nb_seances: number;
        nb_absences: number;
        nb_justifiees: number;
        nb_non_justifiees: number;
        heures_absence: number;
    };
    par_groupe: {
        nom: string;
        nb_absences: number;
        heures_absence: number;
        taux: number;
    }[];
    par_module: {
        module: string;
        nb_seances: number;
        nb_absences: number;
        heures_absence: number;
        taux: number;
    }[];
    top_etudiants: {
        cle: string;
        cne: string;
        nom_complet: string;
        groupe: string;
        nb_absences: number;
        nb_non_justifiees: number;
        taux: number;
    }[];
    evolution: { labels: string[]; valeurs: number[] };
    selects: {
        cycles: SelectOption[];
        niveaux: SelectOption[];
        groupes: SelectOption[];
        modules: SelectOption[];
    };
}

const props = defineProps<StatistiqueIndexInterface>();

//==============================================================================================================
// Filtres : envoyés dans l'URL (page partageable / mise en favori)
//==============================================================================================================
const form = useForm({
    date_debut: props.filtres.date_debut,
    date_fin: props.filtres.date_fin,
    niveau_etude_id: props.filtres.niveau_etude_id ?? '',
    cycle_id: props.filtres.cycle_id ?? '',
    groupe_id: props.filtres.groupe_id ?? '',
    module_id: props.filtres.module_id ?? '',
});

const appliquer = () => {
    router.get('/statistiques', form.data(), { preserveScroll: true });
};

//==============================================================================================================
// Export Excel avec les filtres actuellement appliqués
//==============================================================================================================
const url_export = computed(() => {
    const params = new URLSearchParams();

    Object.entries(props.filtres).forEach(([cle, valeur]) => {
        if (valeur !== null && valeur !== '') params.set(cle, String(valeur));
    });

    return `/statistiques/export?${params.toString()}`;
});
</script>
