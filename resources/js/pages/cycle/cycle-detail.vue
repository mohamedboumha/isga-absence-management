<template>
    <Head :title="titre_page"/>

    <div class="flex flex-col gap-6 p-4">
        <!--=====================================================================================================-->
        <!-- En-tête -->
        <!--=====================================================================================================-->
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex flex-col gap-2">
                <h1 class="text-xl font-semibold">{{ titre_page }}</h1>
                <BadgeCouleur v-if="item.cle && item.couleur" :couleur="item.couleur" :label="item.code ?? ''"
                              class="self-start"/>
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
                    <ChampChaine :mode_vue="mode_vue" nom_champ="code" label="Code" placeholder="ING" required
                                 v-model:valeur="form.code" :error="form.errors.code"/>

                    <ChampChaine :mode_vue="mode_vue" nom_champ="nom" label="Nom" placeholder="Cycle ingénieur" required
                                 v-model:valeur="form.nom" :error="form.errors.nom"/>

                    <ChampNombre
                        :mode_vue="mode_vue"
                        nom_champ="nb_annees"
                        label="Durée"
                        suffixe="an(s)"
                        required
                        :min="1"
                        :max="8"
                        v-model:valeur="form.nb_annees"
                        :error="form.errors.nb_annees"
                    />

                    <ChampCouleur
                        :mode_vue="mode_vue"
                        nom_champ="couleur"
                        label="Couleur"
                        required
                        v-model:valeur="form.couleur"
                        :error="form.errors.couleur"
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
import BadgeCouleur from '@/_core/renders/badge-couleur.vue';
import ChampChaine from '@/_core/renders/champ-chaine.vue';
import ChampCouleur from '@/_core/renders/champ-couleur.vue';
import ChampNombre from '@/_core/renders/champ-nombre.vue';
import {renders, type ModeVue} from '@/_core/renders';

interface Cycle {
    cle: string | null;
    code: string | null;
    nom: string | null;
    nb_annees: number | null;
    couleur: string | null;
    can_be_deleted: boolean;
}

interface CycleDetailInterface {
    mode_vue: ModeVue;
    titre_page: string;
    item: Cycle;
}

const props = defineProps<CycleDetailInterface>();

const form = useForm({
    code: props.item.code ?? '',
    nom: props.item.nom ?? '',
    nb_annees: props.item.nb_annees ?? null,
    couleur: props.item.couleur ?? '#475569',
});

const is_editable = computed(() => props.mode_vue === renders.mode_create || props.mode_vue === renders.mode_edit);

const url_list = '/cycles';
const url_detail = props.item.cle ? `/cycle/${props.item.cle}` : '/cycle';
const url_annuler = props.mode_vue === renders.mode_edit ? url_detail : url_list;

const enregistrer = () =>
    form.post(url_detail, {
        preserveState: 'errors',
        onError: () => toast.error('Certains champs sont à corriger.'),
    });

const supprimer = () =>
    supprimer_avec_confirmation(url_detail, `Supprimer le cycle ${props.item.nom} ?`, "Il sera archivé et n'apparaîtra plus dans les listes.");
</script>
