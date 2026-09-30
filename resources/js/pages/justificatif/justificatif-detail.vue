<template>
    <Head :title="titre_page"/>

    <div class="flex max-w-3xl flex-col gap-6 p-4">
        <!--=====================================================================================================-->
        <!-- En-tête -->
        <!--=====================================================================================================-->
        <div class="flex items-start justify-between gap-4">
            <div class="flex flex-col gap-1">
                <h1 class="text-xl font-semibold">{{ titre_page }}</h1>

                <div v-if="item.cle" class="flex flex-wrap items-center gap-2 text-sm">
                    <span class="rounded-full px-2 py-0.5 text-xs font-medium"
                          :class="classe_statut">{{ item.statut_render }}</span>
                    <span v-if="item.hors_delai"
                          class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">
                        Hors délai (RG-07)
                    </span>
                    <span class="text-muted-foreground">Déposé le {{ date_depot_render }}</span>
                </div>
            </div>

            <div v-if="mode_vue === renders.mode_consultation" class="flex gap-2">
                <template v-if="item.statut === 'EN_ATTENTE'">
                    <Button @click="valider">Valider</Button>
                    <Button variant="outline" @click="refuser">Refuser</Button>
                </template>

                <Button v-if="item.can_be_updated" as-child variant="outline">
                    <Link :href="`${url_detail}/edit`">Modifier</Link>
                </Button>

                <Button v-if="item.can_be_deleted" variant="destructive" @click="supprimer">Supprimer</Button>
            </div>
        </div>

        <!--=====================================================================================================-->
        <!-- Traitement -->
        <!--=====================================================================================================-->
        <p v-if="item.traite_le" class="bg-muted rounded-md p-3 text-sm">
            {{ item.statut === 'VALIDE' ? 'Validé' : 'Refusé' }} le {{ item.traite_le }}
            <template v-if="item.traite_par_nom"> par {{ item.traite_par_nom }}</template>
            .
            <template v-if="item.motif_refus"><br/><span class="font-medium">Motif du refus :</span> {{
                    item.motif_refus
                }}
            </template>
        </p>

        <InputError :message="erreur_action"/>

        <!--=====================================================================================================-->
        <!-- Champs -->
        <!--=====================================================================================================-->
        <form class="grid gap-4" @submit.prevent="enregistrer">
            <ChampSelect
                :mode_vue="mode_vue"
                nom_champ="etudiant_id"
                label="Étudiant"
                placeholder="Choisir un étudiant"
                required
                :options="etudiants"
                v-model:valeur="form.etudiant_id"
                :error="form.errors.etudiant_id"
            />

            <div class="grid grid-cols-3 gap-4">
                <ChampSelect
                    :mode_vue="mode_vue"
                    nom_champ="type"
                    label="Type"
                    required
                    :options="types"
                    v-model:valeur="form.type"
                    :error="form.errors.type"
                />

                <ChampDate
                    :mode_vue="mode_vue"
                    nom_champ="date_debut"
                    label="Du"
                    required
                    v-model:valeur="form.date_debut"
                    :error="form.errors.date_debut"
                />

                <ChampDate
                    :mode_vue="mode_vue"
                    nom_champ="date_fin"
                    label="Au"
                    required
                    v-model:valeur="form.date_fin"
                    :error="form.errors.date_fin"
                />
            </div>

            <ChampTexte
                :mode_vue="mode_vue"
                nom_champ="motif"
                label="Motif"
                :rows="3"
                v-model:valeur="form.motif"
                :error="form.errors.motif"
            />

            <ChampFichier
                :mode_vue="mode_vue"
                nom_champ="fichier"
                label="Document justificatif"
                accept=".pdf,.jpg,.jpeg,.png"
                aide="PDF, JPG ou PNG, 5 Mo maximum."
                :required="mode_vue === renders.mode_create"
                :url_fichier="item.fichier_url"
                :nom_fichier="item.fichier_nom"
                v-model:valeur="form.fichier"
                :error="form.errors.fichier"
            />

            <!--=================================================================================================-->
            <!-- Boutons (create / edit uniquement) -->
            <!--=================================================================================================-->
            <div v-if="is_editable" class="flex gap-2">
                <Button type="submit" :disabled="form.processing">Enregistrer</Button>

                <Button as-child variant="outline">
                    <Link :href="url_annuler">Annuler</Link>
                </Button>
            </div>
        </form>

        <!--=====================================================================================================-->
        <!-- Absences concernées -->
        <!--=====================================================================================================-->
        <div v-if="item.cle" class="flex flex-col gap-2">
            <h2 class="font-medium">Absences sur la période ({{ item.absences.length }})</h2>

            <div class="overflow-hidden rounded-lg border">
                <div v-for="(absence, index) in item.absences" :key="index"
                     class="flex items-center justify-between border-t p-3 text-sm first:border-t-0">
                    <span>{{ absence.date }} · {{ absence.horaire }} · {{ absence.module }}</span>
                    <span
                        class="rounded-full px-2 py-0.5 text-xs font-medium"
                        :class="absence.justifiee ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                    >
                        {{ absence.justifiee ? 'Justifiée' : 'Non justifiée' }}
                    </span>
                </div>

                <p v-if="!item.absences.length" class="text-muted-foreground p-4 text-center text-sm">
                    Aucune absence enregistrée pour cet étudiant sur cette période.
                </p>
            </div>
        </div>
    </div>
</template>


<script setup lang="ts">
import {computed, ref} from 'vue';
import {Head, Link, router, useForm} from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import {Button} from '@/components/ui/button';
import ChampDate from '@/_core/renders/champ-date.vue';
import ChampFichier from '@/_core/renders/champ-fichier.vue';
import ChampSelect from '@/_core/renders/champ-select.vue';
import ChampTexte from '@/_core/renders/champ-texte.vue';
import {renders, type ModeVue} from '@/_core/renders';
import type {SelectOption} from '@/_core/renders/types';

interface AbsenceCouverte {
    date: string;
    horaire: string;
    module: string;
    justifiee: boolean;
}

interface Justificatif {
    cle: string | null;
    etudiant_id: number | null;
    type: string | null;
    date_debut: string | null;
    date_fin: string | null;
    motif: string | null;
    date_depot: string | null;
    fichier_nom: string | null;
    fichier_url: string | null;
    statut: string | null;
    statut_render: string | null;
    motif_refus: string | null;
    hors_delai: boolean;
    traite_par_nom: string | null;
    traite_le: string | null;
    absences: AbsenceCouverte[];
    can_be_updated: boolean;
    can_be_deleted: boolean;
}

interface JustificatifDetailInterface {
    mode_vue: ModeVue;
    titre_page: string;
    item: Justificatif;
    etudiants: SelectOption[];
    types: SelectOption[];
}

const props = defineProps<JustificatifDetailInterface>();

//==============================================================================================================
// Formulaire (avec fichier : envoyé en multipart/form-data)
//==============================================================================================================
const form = useForm({
    etudiant_id: props.item.etudiant_id ?? null,
    type: props.item.type ?? null,
    date_debut: props.item.date_debut ?? '',
    date_fin: props.item.date_fin ?? '',
    motif: props.item.motif ?? '',
    fichier: null as File | null,
});

const is_editable = computed(() => props.mode_vue === renders.mode_create || props.mode_vue === renders.mode_edit);

const date_depot_render = computed(() => props.item.date_depot?.split('-').reverse().join('/') ?? '—');

const classe_statut = computed(() => ({
    'bg-amber-100 text-amber-800': props.item.statut === 'EN_ATTENTE',
    'bg-green-100 text-green-800': props.item.statut === 'VALIDE',
    'bg-red-100 text-red-800': props.item.statut === 'REFUSE',
}));

//==============================================================================================================
// URLs
//==============================================================================================================
const url_list = '/justificatifs';
const url_detail = props.item.cle ? `/justificatif/${props.item.cle}` : '/justificatif';
const url_annuler = props.mode_vue === renders.mode_edit ? url_detail : url_list;

//==============================================================================================================
// Actions
//==============================================================================================================
const erreur_action = ref<string | undefined>();

const enregistrer = () => form.post(url_detail, {forceFormData: true, preserveState: 'errors'});

const valider = () => {
    if (!confirm('Valider ce justificatif ? Les absences de la période seront marquées justifiées.')) return;

    router.post(`${url_detail}/valider`, {}, {
        onError: (errors) => (erreur_action.value = errors.justificatif),
    });
};

const refuser = () => {
    const motif_refus = prompt('Motif du refus (obligatoire) :');

    if (motif_refus === null) return;

    router.post(`${url_detail}/refuser`, {motif_refus}, {
        onError: (errors) => (erreur_action.value = errors.motif_refus ?? errors.justificatif),
    });
};

const supprimer = () => {
    if (!confirm('Supprimer ce justificatif ?')) return;

    router.delete(url_detail, {
        onError: (errors) => (erreur_action.value = errors.justificatif),
    });
};
</script>
