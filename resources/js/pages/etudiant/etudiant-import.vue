<template>
    <Head :title="titre_page"/>

    <div class="flex flex-col gap-6 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold">{{ titre_page }}</h1>
                <p class="text-muted-foreground text-sm">
                    {{
                        annee_active ? `Les étudiants sont inscrits dans leur groupe de l'année ${annee_active}.` : "Aucune année active : la colonne Groupe doit rester vide."
                    }}
                </p>
            </div>

            <Button as-child variant="outline">
                <Link href="/etudiants">Retour à la liste</Link>
            </Button>
        </div>

        <!--=====================================================================================================-->
        <!-- Étape 1 : le modèle et le fichier -->
        <!--=====================================================================================================-->
        <div v-if="!analyse" class="grid items-start gap-6 lg:grid-cols-2">
            <CarteSection titre="1. Préparez le fichier">
                <div class="flex flex-col gap-4 text-sm">
                    <p>Une ligne par étudiant, avec ces colonnes (dans n'importe quel ordre) :</p>

                    <div class="overflow-hidden rounded-md border">
                        <div v-for="colonne in colonnes" :key="colonne.nom"
                             class="flex gap-3 border-b px-3 py-2 last:border-0">
                            <span class="w-36 shrink-0 font-medium">
                                {{ colonne.nom }}
                                <span v-if="colonne.obligatoire" class="text-absent">*</span>
                            </span>
                            <span class="text-muted-foreground">{{ colonne.aide }}</span>
                        </div>
                    </div>

                    <p class="text-muted-foreground">
                        Un étudiant dont le CNE existe déjà est <strong class="text-foreground font-medium">mis à
                        jour</strong> : vous pouvez réimporter le même fichier sans créer de doublons.
                    </p>

                    <Button as-child variant="outline" class="self-start">
                        <a href="/etudiants/import/modele">
                            <FileSpreadsheet class="size-4"/>
                            Télécharger le modèle Excel
                        </a>
                    </Button>
                </div>
            </CarteSection>

            <CarteSection titre="2. Importez-le">
                <form class="flex flex-col gap-4" @submit.prevent="analyser">
                    <ChampFichier
                        :mode_vue="renders.mode_create"
                        nom_champ="fichier"
                        label="Fichier Excel"
                        accept=".xlsx,.xls,.csv"
                        aide="Excel (.xlsx, .xls) ou CSV, 5 Mo et 1 000 lignes au maximum."
                        required
                        v-model:valeur="form.fichier"
                        :error="form.errors.fichier"
                    />

                    <p class="text-muted-foreground text-sm">Rien n'est enregistré à cette étape : vous verrez d'abord
                        le résultat, ligne par ligne.</p>

                    <Button type="submit" class="self-start" :disabled="!form.fichier || form.processing">
                        <Spinner v-if="form.processing" class="size-4"/>
                        Analyser le fichier
                    </Button>
                </form>
            </CarteSection>
        </div>

        <!--=====================================================================================================-->
        <!-- Étape 2 : l'aperçu -->
        <!--=====================================================================================================-->
        <template v-else>
            <CarteSection titre="Aperçu de l'import"
                          :sous_titre="`${analyse.fichier} — ${analyse.apercu.length} ligne(s)`" :avec_marges="false">
                <!-- Compteurs, qui servent aussi de filtres -->
                <div class="flex flex-wrap gap-2 border-b px-5 py-4">
                    <button
                        v-for="filtre in filtres"
                        :key="filtre.valeur"
                        type="button"
                        class="flex items-center gap-2 rounded-md border px-3 py-1.5 text-sm transition-colors"
                        :class="filtre_actif === filtre.valeur ? 'bg-primary text-primary-foreground border-primary' : 'bg-card hover:bg-muted'"
                        :aria-pressed="filtre_actif === filtre.valeur"
                        @click="filtre_actif = filtre.valeur"
                    >
                        <span v-if="filtre.valeur !== 'tous'" class="size-2 rounded-full"
                              :class="pastilles[filtre.valeur as StatutLigne].point"/>
                        {{ filtre.label }}
                        <span class="font-semibold tabular-nums">{{ filtre.nombre }}</span>
                    </button>
                </div>

                <!-- Les lignes -->
                <div class="max-h-[60vh] overflow-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-card sticky top-0 z-10">
                        <tr class="text-muted-foreground border-b text-xs">
                            <th class="h-10 w-16 px-5 text-left font-medium">Ligne</th>
                            <th class="h-10 px-3 text-left font-medium">Étudiant</th>
                            <th class="h-10 px-3 text-left font-medium">Groupe</th>
                            <th class="h-10 px-3 text-left font-medium">Statut</th>
                            <th class="h-10 px-5 text-left font-medium">Détail</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="ligne in lignes_affichees" :key="ligne.ligne"
                            class="border-b align-top last:border-0">
                            <td class="text-muted-foreground px-5 py-3 tabular-nums">{{ ligne.ligne }}</td>
                            <td class="px-3 py-3">
                                <span class="block font-medium">{{ ligne.nom_complet || '—' }}</span>
                                <span class="text-muted-foreground text-xs tabular-nums">{{
                                        ligne.cne || 'CNE manquant'
                                    }}</span>
                            </td>
                            <td class="px-3 py-3">{{ ligne.groupe || '—' }}</td>
                            <td class="px-3 py-3">
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-xs font-medium"
                                        :class="pastilles[ligne.statut].classe">
                                        <span class="size-1.5 rounded-full" :class="pastilles[ligne.statut].point"/>
                                        {{ pastilles[ligne.statut].label }}
                                    </span>
                            </td>
                            <td class="px-5 py-3">
                                <ul v-if="ligne.erreurs.length" class="text-absent flex flex-col gap-0.5">
                                    <li v-for="erreur in ligne.erreurs" :key="erreur">{{ erreur }}</li>
                                </ul>
                                <span v-else-if="ligne.changements.length"
                                      class="text-muted-foreground">{{ ligne.changements.join(', ') }}</span>
                                <span v-else-if="ligne.statut === 'nouveau'" class="text-muted-foreground">{{
                                        ligne.groupe ? `Inscription en ${ligne.groupe}` : 'Sans groupe'
                                    }}</span>
                                <span v-else class="text-muted-foreground">—</span>
                            </td>
                        </tr>
                        </tbody>
                    </table>

                    <p v-if="!lignes_affichees.length" class="text-muted-foreground px-5 py-8 text-center text-sm">
                        Aucune ligne dans cette catégorie.</p>
                </div>
            </CarteSection>

            <!-- Confirmation -->
            <div class="bg-card flex flex-wrap items-center justify-between gap-3 rounded-xl border px-5 py-4">
                <p class="text-sm">
                    <span class="font-semibold tabular-nums">{{ nb_a_importer }}</span> étudiant(s) seront importés.
                    <span v-if="analyse.compteurs.erreur" class="text-muted-foreground">Les {{
                            analyse.compteurs.erreur
                        }} ligne(s) en erreur seront ignorées : corrigez le fichier puis réimportez-le si besoin.</span>
                </p>

                <div class="flex gap-2">
                    <Button variant="outline" :disabled="confirmation.processing" @click="annuler">Choisir un autre
                        fichier
                    </Button>
                    <Button :disabled="!nb_a_importer || confirmation.processing" @click="confirmer">
                        <Spinner v-if="confirmation.processing" class="size-4"/>
                        Importer {{ nb_a_importer }} étudiant(s)
                    </Button>
                </div>
            </div>
        </template>
    </div>
</template>


<script setup lang="ts">
import {computed, ref} from 'vue';
import {Head, Link, router, useForm} from '@inertiajs/vue3';
import {FileSpreadsheet} from '@lucide/vue';
import {Button} from '@/components/ui/button';
import {Spinner} from '@/components/ui/spinner';
import CarteSection from '@/_core/detail/carte-section.vue';
import ChampFichier from '@/_core/renders/champ-fichier.vue';
import {renders} from '@/_core/renders';

type StatutLigne = 'nouveau' | 'modifie' | 'inchange' | 'erreur';

interface LigneApercu {
    ligne: number;
    cne: string | null;
    nom_complet: string;
    groupe: string | null;
    statut: StatutLigne;
    erreurs: string[];
    changements: string[];
}

interface Analyse {
    jeton: string;
    fichier: string;
    annee: string | null;
    compteurs: Record<StatutLigne, number>;
    apercu: LigneApercu[];
}

interface EtudiantImportInterface {
    titre_page: string;
    annee_active: string | null;
    analyse: Analyse | null;
}

const props = defineProps<EtudiantImportInterface>();

const colonnes = [
    {nom: 'CNE', obligatoire: true, aide: "Identifie l'étudiant (R130245678)"},
    {nom: 'Nom', obligatoire: true, aide: 'EL ALAMI'},
    {nom: 'Prénom', obligatoire: true, aide: 'Yasmine'},
    {nom: 'E-mail', obligatoire: false, aide: 'Unique par étudiant'},
    {nom: 'Téléphone', obligatoire: false, aide: '0612345678'},
    {nom: 'Date de naissance', obligatoire: false, aide: '15/03/2007'},
    {nom: 'Groupe', obligatoire: false, aide: "Nom exact d'un groupe de l'année (1AP-A)"},
];

const pastilles: Record<StatutLigne, { label: string; classe: string; point: string }> = {
    nouveau: {label: 'Nouveau', classe: 'bg-present/10 text-present', point: 'bg-present'},
    modifie: {label: 'Mis à jour', classe: 'bg-justifie/10 text-justifie', point: 'bg-justifie'},
    inchange: {label: 'Inchangé', classe: 'bg-muted text-muted-foreground', point: 'bg-muted-foreground/50'},
    erreur: {label: 'Erreur', classe: 'bg-absent/10 text-absent', point: 'bg-absent'},
};

//==============================================================================================================
// Étape 1 : envoi du fichier pour analyse
//==============================================================================================================
const form = useForm({fichier: null as File | null});

const analyser = () => form.post('/etudiants/import/analyser', {forceFormData: true, preserveScroll: true});

//==============================================================================================================
// Étape 2 : filtres de l'aperçu (les erreurs d'abord s'il y en a)
//==============================================================================================================
const filtre_actif = ref<'tous' | StatutLigne>(props.analyse?.compteurs.erreur ? 'erreur' : 'tous');

const filtres = computed(() => {
    const compteurs = props.analyse?.compteurs;

    return [
        {valeur: 'tous', label: 'Toutes', nombre: props.analyse?.apercu.length ?? 0},
        {valeur: 'nouveau', label: 'Nouveaux', nombre: compteurs?.nouveau ?? 0},
        {valeur: 'modifie', label: 'Mis à jour', nombre: compteurs?.modifie ?? 0},
        {valeur: 'inchange', label: 'Inchangés', nombre: compteurs?.inchange ?? 0},
        {valeur: 'erreur', label: 'Erreurs', nombre: compteurs?.erreur ?? 0},
    ] as const;
});

const lignes_affichees = computed(() =>
    (props.analyse?.apercu ?? []).filter((ligne) => filtre_actif.value === 'tous' || ligne.statut === filtre_actif.value),
);

const nb_a_importer = computed(() => (props.analyse?.compteurs.nouveau ?? 0) + (props.analyse?.compteurs.modifie ?? 0));

//==============================================================================================================
// Confirmation ou abandon
//==============================================================================================================
const confirmation = useForm({});

const confirmer = () => confirmation.transform(() => ({jeton: props.analyse?.jeton})).post('/etudiants/import/confirmer');

const annuler = () => router.post('/etudiants/import/annuler');
</script>
