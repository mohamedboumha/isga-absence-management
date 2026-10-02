<template>
    <Head :title="titre_page"/>

    <div class="flex flex-col gap-6 p-4">
        <!--=====================================================================================================-->
        <!-- En-tête -->
        <!--=====================================================================================================-->
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex flex-col gap-2">
                <h1 class="text-xl font-semibold">{{ titre_page }}</h1>
                <StatutPill v-if="item.cle && item.active" statut="en_cours" label="Année en cours" class="self-start"/>
            </div>

            <div v-if="mode_vue === renders.mode_consultation" class="flex flex-wrap gap-2">
                <Button as-child variant="outline">
                    <Link :href="`${url_detail}/edit`">Modifier</Link>
                </Button>

                <Button v-if="item.can_be_deleted" variant="destructive" @click="supprimer">Supprimer</Button>
            </div>
        </div>

        <!--=====================================================================================================-->
        <!-- Informations -->
        <!--=====================================================================================================-->
        <CarteSection titre="Informations">
            <form class="grid gap-4" @submit.prevent="enregistrer">
                <div class="grid gap-4 md:grid-cols-2">
                    <ChampChaine
                        :mode_vue="mode_vue"
                        nom_champ="libelle"
                        label="Libellé"
                        placeholder="2026-2027"
                        required
                        v-model:valeur="form.libelle"
                        :error="form.errors.libelle"
                    />

                    <div class="md:pt-6">
                        <ChampBoolean
                            :mode_vue="mode_vue"
                            nom_champ="active"
                            label="Année en cours"
                            v-model:valeur="form.active"
                            :error="form.errors.active"
                        />
                    </div>

                    <ChampDate
                        :mode_vue="mode_vue"
                        nom_champ="date_debut"
                        label="Date de début"
                        required
                        v-model:valeur="form.date_debut"
                        :error="form.errors.date_debut"
                    />

                    <ChampDate
                        :mode_vue="mode_vue"
                        nom_champ="date_fin"
                        label="Date de fin"
                        required
                        v-model:valeur="form.date_fin"
                        :error="form.errors.date_fin"
                    />
                </div>

                <div v-if="is_editable" class="flex gap-2">
                    <Button type="submit" :disabled="form.processing">Enregistrer</Button>
                    <Button as-child variant="outline">
                        <Link :href="url_annuler">Annuler</Link>
                    </Button>
                </div>
            </form>
        </CarteSection>
    </div>
</template>


<script setup lang="ts">
import {computed} from 'vue';
import {Head, Link, useForm} from '@inertiajs/vue3';
import {toast} from 'vue-sonner';
import {Button} from '@/components/ui/button';
import CarteSection from '@/_core/detail/carte-section.vue';
import {supprimer_avec_confirmation} from '@/_core/dialogs/actions';
import ChampBoolean from '@/_core/renders/champ-boolean.vue';
import ChampChaine from '@/_core/renders/champ-chaine.vue';
import ChampDate from '@/_core/renders/champ-date.vue';
import StatutPill from '@/_core/renders/statut-pill.vue';
import {renders, type ModeVue} from '@/_core/renders';

interface AnneeUniversitaire {
    cle: string | null;
    libelle: string | null;
    date_debut: string | null;
    date_fin: string | null;
    active: boolean;
    can_be_deleted: boolean;
}

interface AnneeUniversitaireDetailInterface {
    mode_vue: ModeVue;
    titre_page: string;
    item: AnneeUniversitaire;
}

const props = defineProps<AnneeUniversitaireDetailInterface>();

const form = useForm({
    libelle: props.item.libelle ?? '',
    date_debut: props.item.date_debut ?? '',
    date_fin: props.item.date_fin ?? '',
    active: props.item.active ?? false,
});

const is_editable = computed(() => props.mode_vue === renders.mode_create || props.mode_vue === renders.mode_edit);

const url_list = '/annees-universitaires';
const url_detail = props.item.cle ? `/annee-universitaire/${props.item.cle}` : '/annee-universitaire';
const url_annuler = props.mode_vue === renders.mode_edit ? url_detail : url_list;

const enregistrer = () =>
    form.post(url_detail, {
        preserveState: 'errors',
        onError: () => toast.error('Certains champs sont à corriger.'),
    });

const supprimer = () =>
    supprimer_avec_confirmation(url_detail, `Supprimer l'année ${props.item.libelle} ?`, "Elle sera archivée et n'apparaîtra plus dans les listes.");
</script>
