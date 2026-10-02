<template>
    <Head :title="titre_page" />

    <div class="flex flex-col gap-6 p-4">
        <!--=====================================================================================================-->
        <!-- En-tête -->
        <!--=====================================================================================================-->
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex flex-col gap-2">
                <h1 class="text-xl font-semibold">{{ titre_page }}</h1>

                <div v-if="item.cle" class="flex flex-wrap items-center gap-2 text-sm">
                    <StatutPill v-if="item.statut" :statut="item.statut" />
                    <StatutPill v-if="item.hors_delai" statut="hors_delai" label="Hors délai (RG-07)" />
                    <span class="text-muted-foreground">Déposé le {{ date_depot_render }}</span>
                </div>
            </div>

            <div v-if="mode_vue === renders.mode_consultation" class="flex flex-wrap gap-2">
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
        <p v-if="item.traite_le" class="bg-champ rounded-md border p-3 text-sm">
            {{ item.statut === 'VALIDE' ? 'Validé' : 'Refusé' }} le {{ item.traite_le }}<template v-if="item.traite_par_nom"> par {{ item.traite_par_nom }}</template>.
            <template v-if="item.motif_refus">
                <br />
                <span class="font-medium">Motif du refus :</span> {{ item.motif_refus }}
            </template>
        </p>

        <!--=====================================================================================================-->
        <!-- Informations -->
        <!--=====================================================================================================-->
        <CarteSection titre="Informations">
            <form class="grid gap-4" @submit.prevent="enregistrer">
                <div class="grid gap-4 md:grid-cols-2">
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

                    <div class="md:col-span-2">
                        <ChampTexte :mode_vue="mode_vue" nom_champ="motif" label="Motif" :rows="3" v-model:valeur="form.motif" :error="form.errors.motif" />
                    </div>

                    <div class="md:col-span-2">
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
                    </div>
                </div>

                <div v-if="is_editable" class="flex gap-2">
                    <Button type="submit" :disabled="form.processing">Enregistrer</Button>
                    <Button as-child variant="outline"><Link :href="url_annuler">Annuler</Link></Button>
                </div>
            </form>
        </CarteSection>

        <!--=====================================================================================================-->
        <!-- Absences concernées -->
        <!--=====================================================================================================-->
        <CarteSection v-if="item.cle" titre="Absences sur la période" :compteur="item.absences.length" :avec_marges="false">
            <div
                v-for="(absence, index) in item.absences"
                :key="index"
                class="flex flex-wrap items-center justify-between gap-3 border-b px-5 py-3 text-sm last:border-0"
            >
                <span class="tabular-nums">{{ absence.date }}, {{ absence.horaire }}, {{ absence.module }}</span>
                <StatutPill :statut="absence.justifiee ? 'justifie' : 'absent'" :label="absence.justifiee ? 'Justifiée' : 'Non justifiée'" />
            </div>

            <p v-if="!item.absences.length" class="text-muted-foreground px-5 py-8 text-center text-sm">
                Aucune absence enregistrée pour cet étudiant sur cette période.
            </p>
        </CarteSection>
    </div>
</template>


<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import CarteSection from '@/_core/detail/carte-section.vue';
import { afficher_erreurs, supprimer_avec_confirmation } from '@/_core/dialogs/actions';
import { demander_confirmation } from '@/_core/dialogs/confirmation';
import ChampDate from '@/_core/renders/champ-date.vue';
import ChampFichier from '@/_core/renders/champ-fichier.vue';
import ChampSelect from '@/_core/renders/champ-select.vue';
import ChampTexte from '@/_core/renders/champ-texte.vue';
import StatutPill from '@/_core/renders/statut-pill.vue';
import { renders, type ModeVue } from '@/_core/renders';
import type { SelectOption } from '@/_core/renders/types';

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

const url_list = '/justificatifs';
const url_detail = props.item.cle ? `/justificatif/${props.item.cle}` : '/justificatif';
const url_annuler = props.mode_vue === renders.mode_edit ? url_detail : url_list;

//==============================================================================================================
// Actions : ce sont des FONCTIONS (() => ...), exécutées seulement au clic
//==============================================================================================================
const enregistrer = () =>
    form.post(url_detail, {
        forceFormData: true,
        preserveState: 'errors',
        onError: () => toast.error('Certains champs sont à corriger.'),
    });

const valider = async () => {
    const { confirme } = await demander_confirmation({
        titre: 'Valider ce justificatif ?',
        message: `${props.item.absences.length} absence(s) sur la période seront marquées comme justifiées.`,
        bouton: 'Valider',
    });

    if (!confirme) return;

    router.post(`${url_detail}/valider`, {}, { onError: afficher_erreurs });
};

const refuser = async () => {
    const { confirme, valeur } = await demander_confirmation({
        titre: 'Refuser ce justificatif ?',
        message: 'Le motif sera conservé dans la fiche du justificatif.',
        bouton: 'Refuser',
        variante: 'destructive',
        champ: { label: 'Motif du refus', placeholder: 'Ex. document illisible', obligatoire: true },
    });

    if (!confirme) return;

    router.post(`${url_detail}/refuser`, { motif_refus: valeur }, { onError: afficher_erreurs });
};

const supprimer = () =>
    supprimer_avec_confirmation(url_detail, 'Supprimer ce justificatif ?', 'Le document joint sera conservé dans les archives.');
</script>
