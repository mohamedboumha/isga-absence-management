<template>
    <Head :title="titre_page"/>

    <div class="flex flex-col gap-6 p-4">
        <!--=====================================================================================================-->
        <!-- En-tête -->
        <!--=====================================================================================================-->
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex flex-col gap-2">
                <h1 class="text-xl font-semibold">{{ titre_page }}</h1>
                <BadgeCouleur v-if="item.cle && couleur_effective" :couleur="couleur_effective" :label="item.code ?? ''"
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
                    <ChampSelect
                        :mode_vue="mode_vue"
                        nom_champ="cycle_id"
                        label="Cycle"
                        placeholder="Choisir un cycle"
                        required
                        :options="cycles"
                        v-model:valeur="form.cycle_id"
                        :error="form.errors.cycle_id"
                    />

                    <ChampCouleur
                        :mode_vue="mode_vue"
                        nom_champ="couleur"
                        label="Couleur"
                        :couleur_heritee="couleur_du_cycle"
                        :label_heritee="couleur_du_cycle ? 'Couleur du cycle' : 'Choisir d\'abord un cycle'"
                        v-model:valeur="form.couleur"
                        :error="form.errors.couleur"
                    />

                    <ChampChaine :mode_vue="mode_vue" nom_champ="code" label="Code" placeholder="IABD" required
                                 v-model:valeur="form.code" :error="form.errors.code"/>

                    <ChampChaine
                        :mode_vue="mode_vue"
                        nom_champ="nom"
                        label="Nom"
                        placeholder="Intelligence artificielle et big data"
                        required
                        v-model:valeur="form.nom"
                        :error="form.errors.nom"
                    />

                    <div class="md:col-span-2">
                        <ChampTexte
                            :mode_vue="mode_vue"
                            nom_champ="description"
                            label="Description"
                            v-model:valeur="form.description"
                            :error="form.errors.description"
                        />
                    </div>
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
import ChampSelect from '@/_core/renders/champ-select.vue';
import ChampTexte from '@/_core/renders/champ-texte.vue';
import {renders, type ModeVue} from '@/_core/renders';
import type {SelectOption} from '@/_core/renders/types';

interface Filiere {
    cle: string | null;
    cycle_id: number | null;
    code: string | null;
    nom: string | null;
    couleur: string | null;
    description: string | null;
    can_be_deleted: boolean;
}

interface CycleOption extends SelectOption {
    couleur: string;
}

interface FiliereDetailInterface {
    mode_vue: ModeVue;
    titre_page: string;
    item: Filiere;
    cycles: CycleOption[];
}

const props = defineProps<FiliereDetailInterface>();

//==============================================================================================================
// Formulaire (couleur null = couleur du cycle)
//==============================================================================================================
const form = useForm({
    cycle_id: props.item.cycle_id ?? null,
    code: props.item.code ?? '',
    nom: props.item.nom ?? '',
    couleur: props.item.couleur ?? null,
    description: props.item.description ?? '',
});

const is_editable = computed(() => props.mode_vue === renders.mode_create || props.mode_vue === renders.mode_edit);

const couleur_du_cycle = computed(() => props.cycles.find((cycle) => cycle.valeur === form.cycle_id)?.couleur ?? null);
const couleur_effective = computed(() => form.couleur || couleur_du_cycle.value);

const url_list = '/filieres';
const url_detail = props.item.cle ? `/filiere/${props.item.cle}` : '/filiere';
const url_annuler = props.mode_vue === renders.mode_edit ? url_detail : url_list;

const enregistrer = () =>
    form.post(url_detail, {
        preserveState: 'errors',
        onError: () => toast.error('Certains champs sont à corriger.'),
    });

const supprimer = () =>
    supprimer_avec_confirmation(url_detail, `Supprimer la filière ${props.item.code} ?`, "Elle sera archivée et n'apparaîtra plus dans les listes.");
</script>
