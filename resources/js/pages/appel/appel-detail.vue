<template>
    <Head :title="titre_page"/>

    <div class="flex max-w-4xl flex-col gap-6 p-4">
        <!--=====================================================================================================-->
        <!-- En-tête : la séance -->
        <!--=====================================================================================================-->
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex flex-col gap-1">
                <h1 class="text-xl font-semibold">{{ titre_page }}</h1>

                <p class="text-muted-foreground text-sm">
                    {{ seance.date }}, {{ seance.horaire }}, {{ seance.type }}
                    <template v-if="seance.salle">, salle {{ seance.salle }}</template>
                </p>

                <p class="text-muted-foreground text-sm">{{ seance.module }} avec {{ seance.enseignant }}</p>
            </div>

            <div class="flex flex-wrap gap-2">
                <BoutonDocument
                    :url="`/seance/${seance.cle}/feuille-presence`"
                    :titre="`Feuille de présence ${seance.groupe} du ${seance.date}`"
                    :nom_fichier="`feuille-presence-${seance.groupe}.pdf`"
                    label="Feuille de présence"
                />

                <Button v-if="url_seance" as-child variant="outline">
                    <Link :href="url_seance">Voir la séance</Link>
                </Button>
            </div>
        </div>

        <!--=====================================================================================================-->
        <!-- Statut de l'appel -->
        <!--=====================================================================================================-->
        <p v-if="seance.appel_fait_le"
           class="bg-present/10 text-present ring-present/25 rounded-md p-3 text-sm ring-1 ring-inset">
            Appel fait le {{ seance.appel_fait_le }}
            <template v-if="seance.appel_fait_par"> par {{ seance.appel_fait_par }}</template>
            .
        </p>

        <p v-else class="bg-muted rounded-md p-3 text-sm">L'appel de cette séance n'a pas encore été fait.</p>

        <p v-if="refus_modification"
           class="bg-absent/5 text-absent ring-absent/25 rounded-md p-3 text-sm ring-1 ring-inset">
            {{ refus_modification }}
        </p>

        <!--=====================================================================================================-->
        <!-- Compteurs et actions rapides -->
        <!--=====================================================================================================-->
        <div class="flex items-center justify-between">
            <p class="text-sm">
                <span class="font-medium tabular-nums">{{ nb_presents }}</span> présent(s),
                <span class="text-absent font-medium tabular-nums">{{ nb_absents }}</span> absent(s)
                sur {{ form.etudiants.length }} étudiant(s)
            </p>

            <Button v-if="is_editable && nb_absents" variant="outline" size="sm" @click="tous_presents">Tous présents
            </Button>
        </div>

        <!--=====================================================================================================-->
        <!-- Liste des étudiants -->
        <!--=====================================================================================================-->
        <div class="overflow-hidden rounded-lg border">
            <div
                v-for="etudiant in form.etudiants"
                :key="etudiant.etudiant_id"
                class="flex flex-col gap-2 border-t p-3 first:border-t-0"
                :class="{ 'bg-absent/5': etudiant.absent }"
            >
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="font-medium">{{ etudiant.nom_complet }}</p>
                        <p class="text-muted-foreground text-xs">{{ etudiant.cne }}</p>
                    </div>

                    <!-- Saisie : Présent / Absent -->
                    <div v-if="is_editable" class="flex overflow-hidden rounded-md border">
                        <button
                            type="button"
                            class="px-3 py-1.5 text-sm"
                            :class="!etudiant.absent ? 'bg-present text-white' : 'hover:bg-muted'"
                            @click="etudiant.absent = false"
                        >
                            Présent
                        </button>
                        <button
                            type="button"
                            class="border-l px-3 py-1.5 text-sm"
                            :class="etudiant.absent ? 'bg-absent text-white' : 'hover:bg-muted'"
                            @click="etudiant.absent = true"
                        >
                            Absent
                        </button>
                    </div>

                    <!-- Consultation : pastille -->
                    <StatutPill v-else
                                :statut="etudiant.absent ? (etudiant.justifiee ? 'justifie' : 'absent') : 'present'"/>
                </div>

                <!-- Remarque (BF-15) -->
                <template v-if="etudiant.absent">
                    <input
                        v-if="is_editable"
                        v-model="etudiant.remarque"
                        type="text"
                        maxlength="255"
                        placeholder="Remarque (facultatif)"
                        class="champ h-9 text-sm"
                    />
                    <p v-else-if="etudiant.remarque" class="text-muted-foreground text-sm">{{ etudiant.remarque }}</p>
                </template>
            </div>

            <p v-if="!form.etudiants.length" class="text-muted-foreground p-6 text-center text-sm">
                Aucun étudiant inscrit dans ce groupe.
            </p>
        </div>

        <InputError :message="erreur_generale"/>

        <!--=====================================================================================================-->
        <!-- Enregistrer -->
        <!--=====================================================================================================-->
        <div v-if="is_editable" class="bg-background sticky bottom-0 flex gap-2 border-t py-3">
            <Button :disabled="form.processing" @click="enregistrer">
                {{ seance.appel_fait_le ? "Mettre à jour l'appel" : "Enregistrer l'appel" }}
            </Button>
        </div>
    </div>
</template>


<script setup lang="ts">
import {computed} from 'vue';
import {Head, Link, useForm} from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import {Button} from '@/components/ui/button';
import BoutonDocument from '@/_core/documents/bouton-document.vue';
import StatutPill from '@/_core/renders/statut-pill.vue';
import {renders, type ModeVue} from '@/_core/renders';

interface SeanceInfos {
    cle: string;
    date: string;
    horaire: string;
    module: string;
    groupe: string;
    enseignant: string;
    type: string;
    salle: string | null;
    annulee: boolean;
    appel_fait_le: string | null;
    appel_fait_par: string | null;
}

interface EtudiantAppel {
    etudiant_id: number;
    cne: string;
    nom_complet: string;
    absent: boolean;
    justifiee: boolean;
    remarque: string | null;
}

interface AppelDetailInterface {
    mode_vue: ModeVue;
    titre_page: string;
    seance: SeanceInfos;
    etudiants: EtudiantAppel[];
    refus_modification: string | null;
    url_seance: string | null;
}

const props = defineProps<AppelDetailInterface>();

//==============================================================================================================
// Formulaire : une ligne par étudiant (présent par défaut, BF-14)
//==============================================================================================================
const form = useForm({
    etudiants: props.etudiants.map((etudiant) => ({...etudiant, remarque: etudiant.remarque ?? ''})),
});

const is_editable = computed(() => props.mode_vue === renders.mode_edit);

const nb_absents = computed(() => form.etudiants.filter((etudiant) => etudiant.absent).length);
const nb_presents = computed(() => form.etudiants.length - nb_absents.value);

//==============================================================================================================
// Première erreur de validation éventuelle (ex. étudiant hors groupe)
//==============================================================================================================
const erreur_generale = computed(() => Object.values(form.errors)[0] as string | undefined);

//==============================================================================================================
// Actions
//==============================================================================================================
const tous_presents = () => {
    form.etudiants.forEach((etudiant) => (etudiant.absent = false));
};

const enregistrer = () => {
    //==========================================================================================================
    // On n'envoie que les absents : les autres sont présents
    //==========================================================================================================
    form
        .transform((data) => ({
            absences: data.etudiants
                .filter((etudiant) => etudiant.absent)
                .map((etudiant) => ({
                    etudiant_id: etudiant.etudiant_id,
                    remarque: etudiant.remarque || null,
                })),
        }))
        .post(`/seance/${props.seance.cle}/appel`, {preserveState: 'errors', preserveScroll: true});
};
</script>
