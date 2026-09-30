<template>
    <Head :title="titre_page"/>

    <div class="flex flex-col gap-6 p-4">
        <div>
            <h1 class="text-xl font-semibold">{{ titre_page }}</h1>
            <p v-if="annee" class="text-muted-foreground text-sm">Année universitaire {{ annee }}</p>
        </div>

        <!--=====================================================================================================-->
        <!-- Indicateurs -->
        <!--=====================================================================================================-->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <CarteKpi titre="Absences aujourd'hui" :valeur="absences_aujourdhui"/>
            <CarteKpi titre="Absences cette semaine" :valeur="absences_semaine"/>
            <CarteKpi
                titre="Justificatifs en attente"
                :valeur="justificatifs_attente"
                :tonalite="justificatifs_attente ? 'alerte' : 'neutre'"
                href="/justificatifs"
            />
            <CarteKpi
                titre="Appels non faits"
                :valeur="appels_manquants"
                sous_titre="Séances passées sans appel"
                :tonalite="appels_manquants ? 'alerte' : 'succes'"
                href="/seances"
            />
            <CarteKpi titre="Taux d'absence" :valeur="`${taux_annee} %`" sous_titre="Sur l'année active (RG-08)"
                      href="/statistiques"/>
        </div>

        <!--=====================================================================================================-->
        <!-- Évolution et classement -->
        <!--=====================================================================================================-->
        <div class="grid gap-4 lg:grid-cols-3">
            <div class="rounded-xl border p-4 lg:col-span-2">
                <h2 class="mb-3 font-medium">Absences des 30 derniers jours</h2>
                <GraphiqueLigne :labels="evolution_30_jours.labels" :valeurs="evolution_30_jours.valeurs"
                                libelle="Absences"/>
            </div>

            <div class="rounded-xl border p-4">
                <h2 class="mb-3 font-medium">Étudiants les plus absents</h2>

                <div v-for="etudiant in top_etudiants" :key="etudiant.cle"
                     class="flex items-center justify-between border-t py-2 text-sm first:border-t-0">
                    <div>
                        <Link :href="`/etudiant/${etudiant.cle}`" class="font-medium hover:underline">
                            {{ etudiant.nom_complet }}
                        </Link>
                        <p class="text-muted-foreground text-xs">{{ etudiant.groupe }} · {{ etudiant.nb_absences }}
                            absence(s)</p>
                    </div>
                    <span class="font-medium tabular-nums">{{ etudiant.taux }} %</span>
                </div>

                <p v-if="!top_etudiants.length" class="text-muted-foreground py-6 text-center text-sm">Aucune absence
                    cette année.</p>
            </div>
        </div>
    </div>
</template>


<script setup lang="ts">
import {Head, Link} from '@inertiajs/vue3';
import GraphiqueLigne from '@/_core/charts/graphique-ligne.vue';
import CarteKpi from '@/_core/dashboard/carte-kpi.vue';

interface TopEtudiant {
    cle: string;
    nom_complet: string;
    groupe: string;
    nb_absences: number;
    taux: number;
}

interface DashboardAdministrationInterface {
    titre_page: string;
    annee: string | null;
    absences_aujourdhui: number;
    absences_semaine: number;
    justificatifs_attente: number;
    appels_manquants: number;
    taux_annee: number;
    evolution_30_jours: { labels: string[]; valeurs: number[] };
    top_etudiants: TopEtudiant[];
}

const props = defineProps<DashboardAdministrationInterface>();
</script>
