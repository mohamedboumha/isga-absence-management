<template>
    <Head :title="titre_page"/>

    <div class="flex flex-col gap-6 p-4">
        <!--=====================================================================================================-->
        <!-- En-tête -->
        <!--=====================================================================================================-->
        <div>
            <h1 class="text-xl font-semibold">Bonjour{{ prenom ? ` ${prenom}` : '' }}</h1>
            <p class="text-muted-foreground text-sm">{{ date_du_jour }}
                <template v-if="annee"> · Année {{ annee }}</template>
            </p>
        </div>

        <!--=====================================================================================================-->
        <!-- Indicateurs -->
        <!--=====================================================================================================-->
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <CarteIndicateur label="Absences aujourd'hui" :valeur="absences_aujourdhui"
                             :tonalite="absences_aujourdhui ? 'absent' : 'neutre'"/>
            <CarteIndicateur
                label="Absences cette semaine"
                :valeur="absences_semaine"
                :actuel="absences_semaine"
                :precedent="absences_semaine_prec"
                detail="vs la semaine dernière à la même date"
            />
            <CarteIndicateur
                label="Taux d'absence sur 30 jours"
                :valeur="formater_nombre(taux_30_jours)"
                unite="%"
                :tonalite="tonalite_taux(taux_30_jours)"
                :actuel="taux_30_jours"
                :precedent="taux_30_jours_prec"
                en_points
                detail="vs les 30 jours précédents"
                href="/statistiques"
            />
            <CarteIndicateur
                label="Séances aujourd'hui"
                :valeur="nb_seances_du_jour"
                :detail="nb_seances_du_jour ? `${nb_appels_faits} appel(s) fait(s) sur ${nb_seances_du_jour}` : 'Aucune séance planifiée'"
                :href="url_seances_du_jour"
            />
        </div>

        <!--=====================================================================================================-->
        <!-- À traiter et séances du jour -->
        <!--=====================================================================================================-->
        <div class="grid items-start gap-6 lg:grid-cols-[22rem_minmax(0,1fr)]">
            <CarteSection titre="À traiter" :avec_marges="false">
                <Link
                    v-for="tache in taches"
                    :key="tache.label"
                    :href="tache.url"
                    class="hover:bg-muted/50 flex items-center gap-3 border-b px-5 py-3.5 text-sm last:border-0"
                >
                    <span
                        class="bg-attente/10 text-attente flex size-9 shrink-0 items-center justify-center rounded-lg">
                        <component :is="tache.icone" class="size-4"/>
                    </span>

                    <span class="min-w-0 flex-1">
                        <span class="block font-medium">{{ tache.label }}</span>
                        <span v-if="tache.detail" class="text-muted-foreground block text-xs">{{ tache.detail }}</span>
                    </span>

                    <span class="text-attente text-lg font-semibold tabular-nums">{{ tache.nombre }}</span>
                    <ChevronRight class="text-muted-foreground size-4"/>
                </Link>

                <div v-if="!taches.length" class="flex flex-col items-center gap-2 px-5 py-8 text-center">
                    <span class="bg-present/10 text-present flex size-10 items-center justify-center rounded-full">
                        <Check class="size-5"/>
                    </span>
                    <p class="font-medium">Tout est à jour</p>
                    <p class="text-muted-foreground text-sm">Aucun appel en retard ni justificatif en attente.</p>
                </div>
            </CarteSection>

            <CarteSection
                titre="Aujourd'hui"
                :sous_titre="nb_seances_du_jour ? `${nb_seances_du_jour} séance(s) dans l'école` : null"
                :lien="seances_du_jour.length ? { url: url_seances_du_jour, label: 'Toutes les séances du jour' } : null"
                :avec_marges="false"
            >
                <Link
                    v-for="seance in seances_du_jour"
                    :key="seance.cle"
                    :href="seance.url"
                    class="hover:bg-muted/50 flex flex-wrap items-center gap-x-4 gap-y-1 border-b px-5 py-3 text-sm last:border-0"
                >
                    <span class="w-28 shrink-0 font-medium tabular-nums">{{ seance.horaire }}</span>

                    <span class="flex min-w-0 flex-1 items-center gap-2">
                        <BadgeCouleur :couleur="seance.couleur" :label="seance.module"/>
                        <span class="truncate">{{ seance.intitule }}</span>
                    </span>

                    <span class="text-muted-foreground hidden w-56 truncate md:block">
                        {{ seance.groupe }} · {{ seance.enseignant }}<template v-if="seance.salle"> · {{
                            seance.salle
                        }}</template>
                    </span>

                    <StatutPill :statut="seance.statut"/>
                </Link>

                <p v-if="!seances_du_jour.length" class="text-muted-foreground px-5 py-10 text-center text-sm">Aucune
                    séance aujourd'hui.</p>
            </CarteSection>
        </div>

        <!--=====================================================================================================-->
        <!-- Évolution, étudiants et groupes -->
        <!--=====================================================================================================-->
        <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_24rem]">
            <CarteSection titre="Absences des 30 derniers jours"
                          :lien="{ url: '/statistiques', label: 'Statistiques' }">
                <GraphiqueLigne :labels="evolution_30_jours.labels" :valeurs="evolution_30_jours.valeurs"
                                libelle="Absences"/>
            </CarteSection>

            <CarteSection titre="Étudiants les plus absents" sous_titre="Sur l'année" :avec_marges="false">
                <Link
                    v-for="etudiant in top_etudiants"
                    :key="etudiant.cle"
                    :href="`/etudiant/${etudiant.cle}`"
                    class="hover:bg-muted/50 flex items-center gap-3 border-b px-5 py-3 text-sm last:border-0"
                >
                    <span class="min-w-0 flex-1">
                        <CellulePersonne :nom="etudiant.nom_complet" :sous_texte="etudiant.groupe ?? etudiant.cne"/>
                    </span>
                    <span class="font-semibold tabular-nums"
                          :class="`text-${tonalite_taux(etudiant.taux)}`">{{ formater_nombre(etudiant.taux) }} %</span>
                </Link>

                <p v-if="!top_etudiants.length" class="text-muted-foreground px-5 py-8 text-center text-sm">Aucune
                    absence cette année.</p>
            </CarteSection>
        </div>

        <CarteSection titre="Groupes les plus absents" sous_titre="Taux d'absence sur l'année"
                      :lien="{ url: '/statistiques', label: 'Tous les groupes' }" :avec_marges="false">
            <ListeBarres :lignes="lignes_groupes" vide="Aucune séance tenue cette année."/>
        </CarteSection>
    </div>
</template>


<script setup lang="ts">
import {computed, type Component} from 'vue';
import {Head, Link} from '@inertiajs/vue3';
import {Check, ChevronRight, ClipboardList, FileClock, FileWarning} from '@lucide/vue';
import GraphiqueLigne from '@/_core/charts/graphique-ligne.vue';
import CarteSection from '@/_core/detail/carte-section.vue';
import {formater_nombre, tonalite_taux} from '@/_core/detail/taux';
import BadgeCouleur from '@/_core/renders/badge-couleur.vue';
import StatutPill from '@/_core/renders/statut-pill.vue';
import CarteIndicateur from '@/_core/statistique/carte-indicateur.vue';
import ListeBarres, {type LigneBarre} from '@/_core/statistique/liste-barres.vue';
import CellulePersonne from '@/_core/table/cellule-personne.vue';

interface SeanceDuJour {
    cle: string;
    horaire: string;
    module: string;
    intitule: string;
    couleur: string;
    groupe: string;
    enseignant: string;
    salle: string | null;
    statut: string;
    url: string;
}

interface TopEtudiant {
    cle: string;
    cne: string;
    nom_complet: string;
    groupe: string | null;
    taux: number;
}

interface GroupeAbsent {
    cle: string;
    nom: string;
    couleur: string;
    url: string;
    nb_absences: number;
    taux: number;
}

interface DashboardAdministrationInterface {
    titre_page: string;
    prenom: string | null;
    date_du_jour: string;
    annee: string | null;
    absences_aujourdhui: number;
    absences_semaine: number;
    absences_semaine_prec: number;
    taux_30_jours: number;
    taux_30_jours_prec: number | null;
    nb_seances_du_jour: number;
    nb_appels_faits: number;
    seances_du_jour: SeanceDuJour[];
    url_seances_du_jour: string;
    a_traiter: { appels_manquants: number; justificatifs_attente: number; justificatifs_hors_delai: number };
    evolution_30_jours: { labels: string[]; valeurs: number[] };
    top_etudiants: TopEtudiant[];
    groupes: GroupeAbsent[];
}

const props = defineProps<DashboardAdministrationInterface>();

//==============================================================================================================
// À traiter : seulement ce qui demande une action (chaque ligne ouvre la liste filtrée)
//==============================================================================================================
const taches = computed(() => {
    const liste: { label: string; detail?: string; nombre: number; url: string; icone: Component }[] = [];
    const {appels_manquants, justificatifs_attente, justificatifs_hors_delai} = props.a_traiter;

    if (appels_manquants) {
        liste.push({
            label: 'Appels non faits',
            detail: 'Séances passées sans appel',
            nombre: appels_manquants,
            url: '/seances?filtres[statut]=appel_a_faire',
            icone: ClipboardList
        });
    }

    if (justificatifs_attente) {
        liste.push({
            label: 'Justificatifs à traiter',
            detail: justificatifs_hors_delai ? `Dont ${justificatifs_hors_delai} déposé(s) hors délai` : 'En attente de validation',
            nombre: justificatifs_attente,
            url: '/justificatifs?statut=EN_ATTENTE',
            icone: justificatifs_hors_delai ? FileWarning : FileClock,
        });
    }

    return liste;
});

const lignes_groupes = computed<LigneBarre[]>(() =>
    props.groupes.map((groupe) => ({
        cle: groupe.cle,
        label: groupe.nom,
        sous_label: `${groupe.nb_absences} absence(s)`,
        valeur: groupe.taux,
        texte: `${formater_nombre(groupe.taux)} %`,
        couleur: groupe.couleur,
        url: groupe.url,
        classe_valeur: `text-${tonalite_taux(groupe.taux)}`,
    })),
);
</script>
