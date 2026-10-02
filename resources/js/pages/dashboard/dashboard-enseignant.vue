<template>
    <Head :title="titre_page"/>

    <div class="flex flex-col gap-6 p-4">
        <!--=====================================================================================================-->
        <!-- En-tête -->
        <!--=====================================================================================================-->
        <div>
            <h1 class="text-xl font-semibold">Bonjour{{ prenom ? ` ${prenom}` : '' }}</h1>
            <p class="text-muted-foreground text-sm">{{ date_du_jour }}</p>
        </div>

        <!--=====================================================================================================-->
        <!-- Indicateurs -->
        <!--=====================================================================================================-->
        <div class="grid gap-4 sm:grid-cols-3">
            <CarteIndicateur
                label="Séances aujourd'hui"
                :valeur="seances_du_jour.filter((seance) => !seance.annulee).length"
                :detail="appels_du_jour_a_faire ? `${appels_du_jour_a_faire} appel(s) à faire` : seances_du_jour.length ? 'Appels à jour' : undefined"
            />
            <CarteIndicateur
                label="Appels en retard"
                :valeur="nb_appels_retard"
                :tonalite="nb_appels_retard ? 'attente' : 'present'"
                :detail="nb_appels_retard ? 'À régulariser avec l\'administration' : 'Aucun retard'"
            />
            <CarteIndicateur
                label="Taux d'absence dans vos séances"
                :valeur="formater_nombre(resume.taux)"
                unite="%"
                :tonalite="tonalite_taux(resume.taux)"
                :detail="`${resume.nb_seances} séance(s) tenue(s) cette année`"
                href="/mes-seances"
            />
        </div>

        <!--=====================================================================================================-->
        <!-- Aujourd'hui : l'action principale, faire l'appel -->
        <!--=====================================================================================================-->
        <CarteSection titre="Aujourd'hui" :avec_marges="false">
            <div v-for="seance in seances_du_jour" :key="seance.cle"
                 class="flex flex-wrap items-center gap-x-4 gap-y-2 border-b px-5 py-4 last:border-0">
                <span class="w-28 shrink-0 text-base font-semibold tabular-nums"
                      :class="{ 'text-muted-foreground line-through': seance.annulee }">
                    {{ seance.horaire }}
                </span>

                <span class="flex min-w-0 flex-1 flex-col gap-1">
                    <span class="flex items-center gap-2">
                        <BadgeCouleur :couleur="seance.couleur" :label="seance.module"/>
                        <span class="truncate font-medium">{{ seance.intitule }}</span>
                    </span>
                    <span class="text-muted-foreground text-sm">
                        {{ seance.groupe }} · {{ seance.type }}<template v-if="seance.salle"> · Salle {{
                            seance.salle
                        }}</template>
                    </span>
                </span>

                <StatutPill v-if="seance.annulee" statut="annulee"/>

                <Button v-else-if="seance.appel_fait" as-child variant="outline">
                    <Link :href="seance.url">
                        <Check class="text-present size-4"/>
                        Appel fait
                    </Link>
                </Button>

                <Button v-else as-child>
                    <Link :href="seance.url">
                        <ClipboardCheck class="size-4"/>
                        Faire l'appel
                    </Link>
                </Button>
            </div>

            <p v-if="!seances_du_jour.length" class="text-muted-foreground px-5 py-10 text-center text-sm">Aucune séance
                aujourd'hui.</p>
        </CarteSection>

        <!--=====================================================================================================-->
        <!-- Retards et prochaines séances -->
        <!--=====================================================================================================-->
        <div class="grid items-start gap-6 lg:grid-cols-2">
            <CarteSection
                titre="Appels en retard"
                :compteur="nb_appels_retard"
                sous_titre="Le délai de saisie est dépassé : contactez l'administration pour les enregistrer"
                :avec_marges="false"
            >
                <LigneSeance v-for="seance in appels_en_retard" :key="seance.cle" :seance="seance"/>

                <p v-if="!appels_en_retard.length" class="text-present px-5 py-8 text-center text-sm font-medium">Tous
                    vos appels sont à jour.</p>
            </CarteSection>

            <CarteSection titre="Prochaines séances" :lien="{ url: '/mes-seances', label: 'Toutes mes séances' }"
                          :avec_marges="false">
                <LigneSeance v-for="seance in a_venir" :key="seance.cle" :seance="seance"/>

                <p v-if="!a_venir.length" class="text-muted-foreground px-5 py-8 text-center text-sm">Aucune séance
                    planifiée à venir.</p>
            </CarteSection>
        </div>
    </div>
</template>


<script setup lang="ts">
import {computed} from 'vue';
import {Head, Link} from '@inertiajs/vue3';
import {Check, ClipboardCheck} from '@lucide/vue';
import {Button} from '@/components/ui/button';
import CarteSection from '@/_core/detail/carte-section.vue';
import LigneSeance from '@/_core/detail/ligne-seance.vue';
import {formater_nombre, tonalite_taux} from '@/_core/detail/taux';
import type {SeanceResume} from '@/_core/detail/types';
import BadgeCouleur from '@/_core/renders/badge-couleur.vue';
import StatutPill from '@/_core/renders/statut-pill.vue';
import CarteIndicateur from '@/_core/statistique/carte-indicateur.vue';

interface SeanceDuJour extends SeanceResume {
    salle: string | null;
    appel_fait: boolean;
    annulee: boolean;
}

interface DashboardEnseignantInterface {
    titre_page: string;
    prenom: string | null;
    date_du_jour: string;
    seances_du_jour: SeanceDuJour[];
    nb_appels_retard: number;
    appels_en_retard: SeanceResume[];
    a_venir: SeanceResume[];
    resume: { nb_seances: number; nb_absences: number; taux: number };
}

const props = defineProps<DashboardEnseignantInterface>();

const appels_du_jour_a_faire = computed(() => props.seances_du_jour.filter((seance) => !seance.annulee && !seance.appel_fait).length);
</script>
