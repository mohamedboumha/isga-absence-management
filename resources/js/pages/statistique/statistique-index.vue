<template>
    <Head :title="titre_page"/>

    <div class="flex flex-col gap-6 p-4">
        <!--=====================================================================================================-->
        <!-- En-tête : période et filtres -->
        <!--=====================================================================================================-->
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold">{{ titre_page }}</h1>
                <p class="text-muted-foreground text-sm tabular-nums">{{ periode.texte }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <div class="bg-champ flex rounded-md border p-0.5" role="group" aria-label="Période">
                    <button
                        v-for="raccourci in raccourcis"
                        :key="raccourci.cle"
                        type="button"
                        class="rounded-[calc(var(--radius-md)-2px)] px-3 py-1.5 text-sm transition-colors"
                        :class="raccourci.actif ? 'bg-primary text-primary-foreground font-medium shadow-xs' : 'text-muted-foreground hover:text-foreground'"
                        :aria-pressed="raccourci.actif"
                        @click="choisir_periode(raccourci.periode)"
                    >
                        {{ raccourci.label }}
                    </button>
                </div>

                <FiltresTable :filtres="filtres" @appliquer="recharger"/>
            </div>
        </div>

        <!-- Filtres actifs -->
        <div v-if="puces.length" class="-mt-2 flex flex-wrap items-center gap-2">
            <span v-for="puce in puces" :key="puce.nom"
                  class="bg-card inline-flex items-center gap-1.5 rounded-full border py-1 pr-1.5 pl-3 text-sm">
                <span class="text-muted-foreground">{{ puce.label }} :</span>
                <span class="font-medium">{{ puce.texte }}</span>
                <button
                    type="button"
                    class="text-muted-foreground hover:bg-muted hover:text-foreground flex size-5 items-center justify-center rounded-full"
                    :aria-label="`Retirer le filtre ${puce.label}`"
                    @click="retirer_filtre(puce.nom)"
                >
                    <X class="size-3.5"/>
                </button>
            </span>

            <button type="button"
                    class="text-muted-foreground hover:text-foreground text-sm underline-offset-4 hover:underline"
                    @click="recharger({})">
                Effacer les filtres
            </button>
        </div>

        <!--=====================================================================================================-->
        <!-- Indicateurs, comparés à la période précédente -->
        <!--=====================================================================================================-->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <CarteIndicateur
                label="Taux d'absence"
                :valeur="formater_nombre(resume.taux)"
                unite="%"
                :tonalite="tonalite_taux(resume.taux)"
                :actuel="resume.taux"
                :precedent="precedent?.taux ?? null"
                en_points
                :detail="precedent ? 'vs période précédente' : 'Heures d\'absence / heures de cours'"
            />
            <CarteIndicateur
                label="Absences"
                :valeur="resume.nb_absences"
                :actuel="resume.nb_absences"
                :precedent="precedent?.nb_absences ?? null"
                :detail="`${formater_nombre(resume.heures_absence)} h au total`"
            />
            <CarteIndicateur
                label="Non justifiées"
                :valeur="resume.nb_non_justifiees"
                :tonalite="resume.nb_non_justifiees ? 'absent' : 'neutre'"
                :detail="resume.nb_absences ? `${Math.round((resume.nb_non_justifiees / resume.nb_absences) * 100)} % des absences` : undefined"
            />
            <CarteIndicateur
                label="Justifiées"
                :valeur="resume.nb_justifiees"
                tonalite="justifie"
                :detail="resume.nb_absences ? `${Math.round((resume.nb_justifiees / resume.nb_absences) * 100)} % des absences` : undefined"
            />
            <CarteIndicateur
                label="Séances tenues"
                :valeur="resume.nb_seances"
                :actuel="resume.nb_seances"
                :precedent="precedent?.nb_seances ?? null"
                hausse_favorable
                detail="Appel fait"
            />
        </div>

        <!--=====================================================================================================-->
        <!-- Rien sur la période -->
        <!--=====================================================================================================-->
        <CarteSection v-if="!resume.nb_seances" titre="Aucune donnée">
            <p class="text-muted-foreground py-6 text-center text-sm">
                Aucune séance tenue sur cette période avec ces filtres. Élargissez la période ou retirez un filtre.
            </p>
        </CarteSection>

        <template v-else>
            <!--=================================================================================================-->
            <!-- Évolution et types de séance -->
            <!--=================================================================================================-->
            <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
                <CarteSection titre="Évolution du taux d'absence" sous_titre="Par semaine (lundi de chaque semaine)">
                    <GraphiqueLigne :labels="evolution.labels" :valeurs="evolution.taux" libelle="Taux d'absence"
                                    suffixe=" %" :couleur="couleur_absent"/>
                </CarteSection>

                <CarteSection titre="Par type de séance" :avec_marges="false">
                    <ListeBarres :lignes="lignes_types"/>
                </CarteSection>
            </div>

            <!--=================================================================================================-->
            <!-- Par groupe et par module -->
            <!--=================================================================================================-->
            <div class="grid items-start gap-6 lg:grid-cols-2">
                <CarteSection titre="Par groupe" sous_titre="Du plus absent au moins absent"
                              :compteur="par_groupe.length" :avec_marges="false">
                    <ListeBarres :lignes="lignes_groupes" :limite="8"/>
                </CarteSection>

                <CarteSection titre="Par module" sous_titre="Du plus absent au moins absent"
                              :compteur="par_module.length" :avec_marges="false">
                    <ListeBarres :lignes="lignes_modules" :limite="8"/>
                </CarteSection>
            </div>

            <!--=================================================================================================-->
            <!-- Créneaux et étudiants -->
            <!--=================================================================================================-->
            <div class="grid items-start gap-6 lg:grid-cols-2">
                <CarteSection titre="Par créneau" sous_titre="Taux d'absence selon le jour et l'heure de début">
                    <CarteChaleur :donnees="creneaux"/>
                </CarteSection>

                <CarteSection titre="Étudiants les plus absents" :compteur="top_etudiants.length" :avec_marges="false">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                            <tr class="text-muted-foreground border-b text-xs">
                                <th class="h-10 px-5 text-left font-medium">Étudiant</th>
                                <th class="h-10 px-3 text-left font-medium">Groupe</th>
                                <th class="h-10 px-3 text-right font-medium">Non just.</th>
                                <th class="h-10 px-5 text-right font-medium">Taux</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr
                                v-for="etudiant in top_etudiants"
                                :key="etudiant.cle"
                                class="hover:bg-muted/50 cursor-pointer border-b last:border-0"
                                @click="router.visit(`/etudiant/${etudiant.cle}`)"
                            >
                                <td class="h-14 px-5">
                                    <CellulePersonne :nom="etudiant.nom_complet" :sous_texte="etudiant.cne"/>
                                </td>
                                <td class="px-3">
                                    <BadgeCouleur v-if="etudiant.groupe" :couleur="etudiant.couleur ?? '#475569'"
                                                  :label="etudiant.groupe"/>
                                </td>
                                <td class="px-3 text-right tabular-nums"
                                    :class="{ 'text-absent font-medium': etudiant.nb_non_justifiees }">
                                    {{ etudiant.nb_non_justifiees }}
                                </td>
                                <td class="px-5 text-right font-semibold tabular-nums"
                                    :class="`text-${tonalite_taux(etudiant.taux)}`">
                                    {{ formater_nombre(etudiant.taux) }} %
                                </td>
                            </tr>

                            <tr v-if="!top_etudiants.length">
                                <td colspan="4" class="text-muted-foreground px-5 py-8 text-center">Aucune absence sur
                                    cette période.
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </CarteSection>
            </div>
        </template>
    </div>
</template>


<script setup lang="ts">
import {computed} from 'vue';
import {Head, router} from '@inertiajs/vue3';
import {X} from '@lucide/vue';
import GraphiqueLigne from '@/_core/charts/graphique-ligne.vue';
import CarteSection from '@/_core/detail/carte-section.vue';
import {formater_nombre, tonalite_taux} from '@/_core/detail/taux';
import BadgeCouleur from '@/_core/renders/badge-couleur.vue';
import CarteChaleur, {type DonneesChaleur} from '@/_core/statistique/carte-chaleur.vue';
import CarteIndicateur from '@/_core/statistique/carte-indicateur.vue';
import ListeBarres, {type LigneBarre} from '@/_core/statistique/liste-barres.vue';
import CellulePersonne from '@/_core/table/cellule-personne.vue';
import FiltresTable from '@/_core/table/filtres-table.vue';
import type {TableFiltre, ValeurPeriode} from '@/_core/table/types';

interface Resume {
    nb_seances: number;
    nb_absences: number;
    nb_justifiees: number;
    nb_non_justifiees: number;
    heures_absence: number;
    taux: number;
}

interface LigneGroupe {
    cle: string;
    nom: string;
    couleur: string;
    url: string;
    nb_absences: number;
    taux: number;
}

interface LigneModule {
    cle: string;
    code: string;
    intitule: string;
    couleur: string;
    url: string;
    nb_seances: number;
    nb_absences: number;
    taux: number;
}

interface LigneType {
    type: string;
    nb_seances: number;
    nb_absences: number;
    taux: number;
}

interface TopEtudiant {
    cle: string;
    cne: string;
    nom_complet: string;
    groupe: string | null;
    couleur: string | null;
    nb_absences: number;
    nb_non_justifiees: number;
    taux: number;
}

interface StatistiqueIndexInterface {
    titre_page: string;
    filtres: TableFiltre[];
    periode: { du: string; au: string; texte: string };
    annee: { libelle: string; du: string | null; au: string | null } | null;
    resume: Resume;
    precedent: Resume | null;
    evolution: { labels: string[]; taux: number[]; absences: number[] };
    par_groupe: LigneGroupe[];
    par_module: LigneModule[];
    par_type: LigneType[];
    creneaux: DonneesChaleur;
    top_etudiants: TopEtudiant[];
}

const props = defineProps<StatistiqueIndexInterface>();

const couleur_absent = '#D91D36';

//==============================================================================================================
// Rechargement avec de nouveaux filtres (gardés dans l'URL : la page filtrée peut être partagée)
//==============================================================================================================
const valeurs_filtres = computed(() =>
    Object.fromEntries(props.filtres.filter((filtre) => filtre.valeur !== null).map((filtre) => [filtre.nom, filtre.valeur])),
);

const recharger = (valeurs: Record<string, unknown>) =>
    router.get('/statistiques', {filtres: valeurs}, {preserveScroll: true, preserveState: true, replace: true});

const retirer_filtre = (nom: string) => {
    const valeurs = {...valeurs_filtres.value};

    delete valeurs[nom];

    recharger(valeurs);
};

//==============================================================================================================
// Raccourcis de période : Année (= sans filtre de période), 30 jours, ce mois, cette semaine
//==============================================================================================================
const en_chaine = (date: Date) =>
    `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;

const aujourdhui = new Date();

const raccourcis = computed(() => {
    const periode_actuelle = valeurs_filtres.value.periode as ValeurPeriode | undefined;
    const lundi = new Date(aujourdhui.getFullYear(), aujourdhui.getMonth(), aujourdhui.getDate() - ((aujourdhui.getDay() + 6) % 7));

    const definitions: { cle: string; label: string; periode: ValeurPeriode | null }[] = [
        {cle: 'annee', label: props.annee ? 'Année' : '12 mois', periode: null},
        {
            cle: '30j',
            label: '30 jours',
            periode: {
                du: en_chaine(new Date(aujourdhui.getFullYear(), aujourdhui.getMonth(), aujourdhui.getDate() - 29)),
                au: en_chaine(aujourdhui)
            }
        },
        {
            cle: 'mois',
            label: 'Ce mois',
            periode: {
                du: en_chaine(new Date(aujourdhui.getFullYear(), aujourdhui.getMonth(), 1)),
                au: en_chaine(aujourdhui)
            }
        },
        {cle: 'semaine', label: 'Cette semaine', periode: {du: en_chaine(lundi), au: en_chaine(aujourdhui)}},
    ];

    return definitions.map((raccourci) => ({
        ...raccourci,
        actif: raccourci.periode === null ? !periode_actuelle : periode_actuelle?.du === raccourci.periode.du && periode_actuelle?.au === raccourci.periode.au,
    }));
});

const choisir_periode = (periode: ValeurPeriode | null) => {
    const valeurs = {...valeurs_filtres.value};

    if (periode) valeurs.periode = periode;
    else delete valeurs.periode;

    recharger(valeurs);
};

//==============================================================================================================
// Puces des filtres actifs (la période choisie par un raccourci y figure aussi)
//==============================================================================================================
const date_courte = (date: string | null) => (date ? date.split('-').reverse().join('/') : '');

const puces = computed(() =>
    props.filtres
        .filter((filtre) => filtre.valeur !== null)
        .map((filtre) => {
            if (filtre.type === 'periode') {
                const {du, au} = filtre.valeur as ValeurPeriode;

                return {
                    nom: filtre.nom,
                    label: filtre.label,
                    texte: du && au ? `du ${date_courte(du)} au ${date_courte(au)}` : du ? `à partir du ${date_courte(du)}` : `jusqu'au ${date_courte(au)}`,
                };
            }

            return {
                nom: filtre.nom,
                label: filtre.label,
                texte: filtre.options.find((option) => String(option.valeur) === String(filtre.valeur))?.label ?? String(filtre.valeur),
            };
        }),
);

//==============================================================================================================
// Lignes des classements (barres)
//==============================================================================================================
const texte_taux = (taux: number) => `${formater_nombre(taux)} %`;

const lignes_groupes = computed<LigneBarre[]>(() =>
    props.par_groupe.map((groupe) => ({
        cle: groupe.cle,
        label: groupe.nom,
        sous_label: `${groupe.nb_absences} absence(s)`,
        valeur: groupe.taux,
        texte: texte_taux(groupe.taux),
        couleur: groupe.couleur,
        url: groupe.url,
        classe_valeur: `text-${tonalite_taux(groupe.taux)}`,
    })),
);

const lignes_modules = computed<LigneBarre[]>(() =>
    props.par_module.map((module) => ({
        cle: module.cle,
        label: module.code,
        sous_label: module.intitule,
        valeur: module.taux,
        texte: texte_taux(module.taux),
        couleur: module.couleur,
        url: module.url,
        classe_valeur: `text-${tonalite_taux(module.taux)}`,
    })),
);

const lignes_types = computed<LigneBarre[]>(() =>
    props.par_type.map((type) => ({
        cle: type.type,
        label: type.type,
        sous_label: `${type.nb_seances} séance(s), ${type.nb_absences} absence(s)`,
        valeur: type.taux,
        texte: texte_taux(type.taux),
        classe_valeur: `text-${tonalite_taux(type.taux)}`,
    })),
);
</script>
