<template>
    <Head :title="titre_page" />

    <div class="flex flex-col gap-6 p-4">
        <div>
            <h1 class="text-xl font-semibold">Bonjour {{ prenom }}</h1>
            <p class="text-sm text-muted-foreground">
                Vos séances d'aujourd'hui et les appels à faire.
            </p>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <CarteKpi
                titre="Séances aujourd'hui"
                :valeur="seances_du_jour.length"
            />
            <CarteKpi
                titre="Appels en retard"
                :valeur="appels_en_retard.length"
                :tonalite="appels_en_retard.length ? 'alerte' : 'succes'"
            />
            <CarteKpi
                titre="Séances des 7 prochains jours"
                :valeur="seances_a_venir"
                href="/mes-seances"
            />
        </div>

        <!--=====================================================================================================-->
        <!-- Aujourd'hui -->
        <!--=====================================================================================================-->
        <div class="rounded-xl border">
            <h2 class="border-b p-4 font-medium">Aujourd'hui</h2>

            <Link
                v-for="(seance, index) in seances_du_jour"
                :key="index"
                :href="seance.url_appel"
                class="flex items-center justify-between border-t p-4 first:border-t-0 hover:bg-muted/40"
            >
                <div>
                    <p class="font-medium">
                        {{ seance.horaire }} · {{ seance.module }}
                    </p>
                    <p class="text-sm text-muted-foreground">
                        {{ seance.groupe }}
                        <template v-if="seance.salle">
                            · Salle {{ seance.salle }}</template
                        >
                    </p>
                </div>

                <span
                    v-if="seance.annulee"
                    class="rounded-full bg-muted px-2 py-0.5 text-xs"
                    >Annulée</span
                >
                <span
                    v-else-if="seance.appel_fait"
                    class="rounded-full bg-green-100 px-2 py-0.5 text-xs text-green-800"
                    >Appel fait</span
                >
                <span
                    v-else
                    class="rounded-full bg-primary px-3 py-1 text-xs font-medium text-primary-foreground"
                    >Faire l'appel</span
                >
            </Link>

            <p
                v-if="!seances_du_jour.length"
                class="p-6 text-center text-sm text-muted-foreground"
            >
                Aucune séance aujourd'hui.
            </p>
        </div>

        <!--=====================================================================================================-->
        <!-- Appels en retard -->
        <!--=====================================================================================================-->
        <div
            v-if="appels_en_retard.length"
            class="rounded-xl border border-amber-300"
        >
            <div class="border-b border-amber-300 p-4">
                <h2 class="font-medium">Appels en retard</h2>
                <p class="text-sm text-muted-foreground">
                    Le délai de saisie est dépassé : contactez l'administration
                    pour enregistrer ces appels.
                </p>
            </div>

            <Link
                v-for="(seance, index) in appels_en_retard"
                :key="index"
                :href="seance.url_appel"
                class="flex items-center justify-between border-t p-4 text-sm first:border-t-0 hover:bg-muted/40"
            >
                <span
                    >{{ seance.date }} · {{ seance.horaire }} ·
                    {{ seance.module }} · {{ seance.groupe }}</span
                >
            </Link>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import CarteKpi from '@/_core/dashboard/carte-kpi.vue';

interface SeanceLigne {
    date: string;
    horaire: string;
    module: string;
    groupe: string;
    salle: string | null;
    annulee: boolean;
    appel_fait: boolean;
    url_appel: string;
}

interface DashboardEnseignantInterface {
    titre_page: string;
    prenom: string | null;
    seances_du_jour: SeanceLigne[];
    appels_en_retard: SeanceLigne[];
    seances_a_venir: number;
}

const props = defineProps<DashboardEnseignantInterface>();
</script>
