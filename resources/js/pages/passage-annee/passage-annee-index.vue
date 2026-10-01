<template>
    <Head :title="titre_page"/>

    <div class="flex flex-col gap-6 p-4">
        <!--=====================================================================================================-->
        <!-- En-tête -->
        <!--=====================================================================================================-->
        <div>
            <h1 class="text-xl font-semibold">{{ titre_page }}</h1>
            <p class="text-muted-foreground max-w-3xl text-sm">
                Groupe par groupe, décidez du devenir de chaque étudiant. Les inscriptions de l'année cible sont créées
                en une seule
                opération ; l'année source n'est jamais modifiée, seule la décision y est notée.
            </p>
        </div>

        <!--=====================================================================================================-->
        <!-- Années source et cible -->
        <!--=====================================================================================================-->
        <div v-if="annees.length" class="flex flex-wrap items-end gap-3 rounded-xl border p-4">
            <label class="grid gap-1 text-sm font-medium">
                Année source
                <select
                    :value="source_cle ?? ''"
                    class="border-input bg-background h-9 rounded-md border px-3 text-sm font-normal"
                    @change="changer_annee('source', $event)"
                >
                    <option v-for="annee in annees" :key="annee.valeur" :value="annee.valeur">{{ annee.label }}</option>
                </select>
            </label>

            <ArrowRight class="text-muted-foreground mb-2.5 size-4" aria-hidden="true"/>

            <label class="grid gap-1 text-sm font-medium">
                Année cible
                <select
                    :value="cible_cle ?? ''"
                    class="border-input bg-background h-9 rounded-md border px-3 text-sm font-normal"
                    @change="changer_annee('cible', $event)"
                >
                    <option value="" disabled>Choisir...</option>
                    <option v-for="annee in annees" :key="annee.valeur" :value="annee.valeur">{{ annee.label }}</option>
                </select>
            </label>
        </div>

        <p v-if="!annees.length" class="bg-muted rounded-md p-3 text-sm">
            Aucune année universitaire.
            <Link href="/annee-universitaire" class="underline underline-offset-4">Créez une année</Link>
            pour commencer.
        </p>

        <p v-else-if="!cible_cle" class="border-attente/40 bg-attente/10 rounded-md border p-3 text-sm">
            Aucune année après {{ source_libelle }}.
            <Link href="/annee-universitaire" class="underline underline-offset-4">Créez l'année suivante</Link>
            puis revenez ici.
        </p>

        <div v-else class="grid gap-6 lg:grid-cols-[280px_1fr]">
            <!--=================================================================================================-->
            <!-- Groupes de l'année source -->
            <!--=================================================================================================-->
            <nav class="flex flex-col gap-1" aria-label="Groupes">
                <p class="text-muted-foreground mb-1 text-xs font-medium tracking-wide uppercase">Groupes
                    {{ source_libelle }}</p>

                <Link
                    v-for="groupe in groupes"
                    :key="groupe.cle"
                    :href="url_groupe(groupe.cle)"
                    preserve-scroll
                    class="flex items-center justify-between gap-2 rounded-md border px-3 py-2 text-sm"
                    :class="groupe.cle === detail?.groupe.cle ? 'border-primary bg-muted/60' : 'hover:bg-muted/40'"
                >
                    <span>
                        <span class="font-medium">{{ groupe.nom }}</span>
                        <span class="text-muted-foreground block text-xs">{{ groupe.niveau }} · {{ groupe.effectif }} étudiant(s)</span>
                    </span>

                    <StatutPill
                        :statut="groupe.effectif && groupe.nb_decisions === groupe.effectif ? 'traite' : 'a_traiter'"/>
                </Link>

                <p v-if="!groupes.length" class="text-muted-foreground p-3 text-sm">Aucun groupe en {{
                        source_libelle
                    }}.</p>
            </nav>

            <!--=================================================================================================-->
            <!-- Aucun groupe choisi -->
            <!--=================================================================================================-->
            <div
                v-if="!detail"
                class="text-muted-foreground flex min-h-48 items-center justify-center rounded-xl border border-dashed p-6 text-center text-sm"
            >
                Choisissez un groupe à gauche pour préparer son passage en {{ cible_libelle }}.
            </div>

            <!--=================================================================================================-->
            <!-- Passage du groupe choisi -->
            <!--=================================================================================================-->
            <form v-else class="flex flex-col gap-6" @submit.prevent="valider">
                <div>
                    <h2 class="text-lg font-semibold">{{ detail.groupe.nom }} — {{ detail.groupe.niveau_libelle }}</h2>
                    <p class="text-muted-foreground text-sm">
                        <template v-if="detail.groupe.est_derniere_annee">Dernière année du cycle : les étudiants admis
                            obtiennent leur diplôme.
                        </template>
                        <template v-else-if="detail.suivants.length">Année suivante :
                            {{ detail.suivants.map((suivant) => suivant.code).join(' ou ') }}
                        </template>
                        <template v-else>
                            Aucun niveau suivant n'est défini pour {{ detail.groupe.niveau_code }} :
                            <Link href="/niveaux-etudes" class="underline underline-offset-4">complétez son parcours
                            </Link>
                            .
                        </template>
                    </p>
                </div>

                <!--=============================================================================================-->
                <!-- Groupes d'accueil dans l'année cible -->
                <!--=============================================================================================-->
                <div class="rounded-xl border p-4">
                    <h3 class="mb-1 font-medium">Groupes d'accueil en {{ cible_libelle }}</h3>
                    <p class="text-muted-foreground mb-3 text-sm">Un groupe à créer n'est créé que si au moins un
                        étudiant y va.</p>

                    <div
                        v-for="(destination, index) in form.destinations"
                        :key="destination.niveau_id"
                        class="grid items-center gap-2 border-t py-2 first:border-t-0 sm:grid-cols-[1fr_260px]"
                    >
                        <span class="text-sm">
                            {{ detail.destinations[index].est_redoublement ? 'Redoublants' : 'Admis en' }}
                            <strong>{{ detail.destinations[index].code }}</strong>
                            <span class="text-muted-foreground"> — {{ detail.destinations[index].libelle }}</span>
                        </span>

                        <div>
                            <select v-model="destination.groupe_id"
                                    class="border-input bg-background h-9 w-full rounded-md border px-3 text-sm">
                                <option :value="null">Créer {{ detail.destinations[index].nom_automatique }}</option>
                                <option v-for="groupe in detail.destinations[index].groupes" :key="groupe.valeur"
                                        :value="groupe.valeur">
                                    {{ groupe.label }}
                                </option>
                            </select>
                            <InputError :message="form.errors[`destinations.${index}.groupe_id`]"/>
                        </div>
                    </div>
                </div>

                <!--=============================================================================================-->
                <!-- Décisions -->
                <!--=============================================================================================-->
                <div class="flex flex-wrap items-center gap-2 text-sm">
                    <span class="text-muted-foreground">Tout marquer :</span>
                    <Button
                        v-for="decision in detail.decisions_possibles"
                        :key="decision"
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="appliquer_a_tous(decision)"
                    >
                        {{ decisions[decision] }}
                    </Button>
                </div>

                <div class="overflow-x-auto rounded-xl border">
                    <table class="w-full text-sm">
                        <thead class="bg-muted/50 text-left">
                        <tr>
                            <th class="px-4 py-2 font-medium">Étudiant</th>
                            <th class="px-4 py-2 text-right font-medium">Absences</th>
                            <th class="px-4 py-2 font-medium">Décision</th>
                            <th class="px-4 py-2 font-medium">Année suivante</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="(ligne, index) in form.etudiants" :key="ligne.inscription_id"
                            class="border-t align-top">
                            <td class="px-4 py-2">
                                <p class="font-medium">{{ detail.etudiants[index].nom_complet }}</p>
                                <p class="text-muted-foreground text-xs">{{ detail.etudiants[index].cne }}</p>
                            </td>

                            <td class="px-4 py-2 text-right tabular-nums">{{ detail.etudiants[index].nb_absences }}</td>

                            <td class="px-4 py-2">
                                <select v-model="ligne.decision"
                                        class="border-input bg-background h-9 rounded-md border px-3 text-sm">
                                    <option v-for="decision in detail.decisions_possibles" :key="decision"
                                            :value="decision">
                                        {{ decisions[decision] }}
                                    </option>
                                </select>
                                <InputError :message="form.errors[`etudiants.${index}.decision`]"/>
                            </td>

                            <td class="px-4 py-2">
                                <template v-if="ligne.decision === 'ADMIS'">
                                    <select
                                        v-if="detail.suivants.length > 1"
                                        v-model="ligne.niveau_id"
                                        class="border-input bg-background h-9 rounded-md border px-3 text-sm"
                                    >
                                        <option :value="null" disabled>Choisir le niveau...</option>
                                        <option v-for="suivant in detail.suivants" :key="suivant.id"
                                                :value="suivant.id">{{ suivant.code }}
                                        </option>
                                    </select>
                                    <span v-else-if="detail.suivants.length === 1">{{ detail.suivants[0].code }}</span>
                                </template>

                                <span v-else-if="ligne.decision === 'REDOUBLANT'">{{ detail.groupe.niveau_code }}</span>
                                <StatutPill v-else-if="ligne.decision === 'DIPLOME'" statut="DIPLOME"/>
                                <span v-else class="text-muted-foreground">—</span>

                                <InputError :message="form.errors[`etudiants.${index}.niveau_id`]"/>
                            </td>
                        </tr>

                        <tr v-if="!form.etudiants.length">
                            <td colspan="4" class="text-muted-foreground px-4 py-6 text-center">Aucun étudiant inscrit
                                dans ce groupe.
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <!--=============================================================================================-->
                <!-- Récapitulatif et validation -->
                <!--=============================================================================================-->
                <div
                    class="bg-background sticky bottom-0 flex flex-wrap items-center justify-between gap-3 border-t py-3">
                    <p class="text-sm">
                        <template v-for="(nombre, decision) in recapitulatif" :key="decision">
                            <span v-if="nombre" class="mr-3"><strong class="tabular-nums">{{
                                    nombre
                                }}</strong> {{ decisions[decision].toLowerCase() }}</span>
                        </template>
                    </p>

                    <Button type="submit" :disabled="form.processing || !form.etudiants.length">Valider le passage
                    </Button>
                </div>
            </form>
        </div>
    </div>
</template>


<script setup lang="ts">
import {computed} from 'vue';
import {Head, Link, router, useForm} from '@inertiajs/vue3';
import {ArrowRight} from '@lucide/vue';
import {toast} from 'vue-sonner';
import InputError from '@/components/InputError.vue';
import {Button} from '@/components/ui/button';
import {demander_confirmation} from '@/_core/dialogs/confirmation';
import StatutPill from '@/_core/renders/statut-pill.vue';
import type {SelectOption} from '@/_core/renders/types';

interface GroupeSource {
    cle: string;
    nom: string;
    niveau: string;
    effectif: number;
    nb_decisions: number;
}

interface Destination {
    niveau_id: number;
    code: string;
    libelle: string;
    est_redoublement: boolean;
    groupes: SelectOption[];
    groupe_id_defaut: number | null;
    nom_automatique: string;
}

interface EtudiantPassage {
    inscription_id: number;
    nom_complet: string;
    cne: string;
    nb_absences: number;
    decision: string | null;
    niveau_cible_id: number | null;
}

interface DetailGroupe {
    groupe: {
        id: number;
        cle: string;
        nom: string;
        niveau_code: string;
        niveau_libelle: string;
        est_derniere_annee: boolean;
    };
    suivants: { id: number; code: string; libelle: string }[];
    decisions_possibles: string[];
    destinations: Destination[];
    etudiants: EtudiantPassage[];
}

interface PassageAnneeIndexInterface {
    titre_page: string;
    annees: SelectOption[];
    source_cle: string | null;
    source_libelle: string | null;
    cible_cle: string | null;
    cible_id: number | null;
    cible_libelle: string | null;
    groupes: GroupeSource[];
    detail: DetailGroupe | null;
    decisions: Record<string, string>;
}

const props = defineProps<PassageAnneeIndexInterface>();

//==============================================================================================================
// Valeurs par défaut : "Diplômé" en dernière année, "Admis" sinon ; niveau suivant s'il n'y en a qu'un
//==============================================================================================================
function decision_par_defaut(): string {
    return props.detail?.groupe.est_derniere_annee ? 'DIPLOME' : 'ADMIS';
}

function niveau_par_defaut(): number | null {
    return props.detail?.suivants.length === 1 ? props.detail.suivants[0].id : null;
}

//==============================================================================================================
// Formulaire : groupes d'accueil + une ligne par étudiant
//==============================================================================================================
const form = useForm({
    annee_cible_id: props.cible_id,
    groupe_id: props.detail?.groupe.id ?? null,
    destinations: (props.detail?.destinations ?? []).map((destination) => ({
        niveau_id: destination.niveau_id,
        groupe_id: destination.groupe_id_defaut,
    })),
    etudiants: (props.detail?.etudiants ?? []).map((etudiant) => ({
        inscription_id: etudiant.inscription_id,
        decision: etudiant.decision ?? decision_par_defaut(),
        niveau_id: etudiant.niveau_cible_id ?? niveau_par_defaut(),
    })),
});

//==============================================================================================================
// Récapitulatif : nombre d'étudiants par décision
//==============================================================================================================
const recapitulatif = computed(() => {
    const compteurs: Record<string, number> = {ADMIS: 0, REDOUBLANT: 0, DIPLOME: 0, SORTANT: 0};

    form.etudiants.forEach((ligne) => compteurs[ligne.decision]++);

    return compteurs;
});

//==============================================================================================================
// Navigation
//==============================================================================================================
const changer_annee = (type: 'source' | 'cible', event: Event) => {
    const valeur = (event.target as HTMLSelectElement).value;

    router.get('/passage-annee', {
        source: type === 'source' ? valeur : props.source_cle,
        cible: type === 'cible' ? valeur : undefined, // nouvelle source : l'année cible est recalculée
    });
};

const url_groupe = (cle: string) => `/passage-annee?source=${props.source_cle}&cible=${props.cible_cle}&groupe=${cle}`;

//==============================================================================================================
// Actions
//==============================================================================================================
const appliquer_a_tous = (decision: string) => {
    form.etudiants.forEach((ligne) => (ligne.decision = decision));
};

const valider = async () => {
    const resume = Object.entries(recapitulatif.value)
        .filter(([, nombre]) => nombre)
        .map(([decision, nombre]) => `${nombre} ${props.decisions[decision].toLowerCase()}`)
        .join(', ');

    const {confirme} = await demander_confirmation({
        titre: `Valider le passage de ${props.detail?.groupe.nom} ?`,
        message: `${resume}. Les inscriptions en ${props.cible_libelle} seront créées ou mises à jour.`,
        bouton: 'Valider le passage',
    });

    if (!confirme) return;

    form.post('/passage-annee', {
        preserveScroll: true,
        preserveState: 'errors',
        onError: () => toast.error('Certaines décisions sont à compléter.'),
    });
};
</script>
