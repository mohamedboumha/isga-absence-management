<template>
    <Head :title="titre_page" />

    <div class="flex max-w-2xl flex-col gap-6 p-4">
        <!--=====================================================================================================-->
        <!-- En-tête -->
        <!--=====================================================================================================-->
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold">{{ titre_page }}</h1>

            <div
                v-if="mode_vue === renders.mode_consultation"
                class="flex gap-2"
            >
                <Button as-child variant="outline">
                    <Link :href="`${url_detail}/edit`">Modifier</Link>
                </Button>

                <Button
                    v-if="item.can_be_deleted"
                    variant="destructive"
                    @click="supprimer"
                    >Supprimer</Button
                >
            </div>
        </div>

        <!--=====================================================================================================-->
        <!-- Champs -->
        <!--=====================================================================================================-->
        <form class="grid gap-4" @submit.prevent="enregistrer">
            <div class="grid grid-cols-3 gap-4">
                <ChampChaine
                    :mode_vue="mode_vue"
                    nom_champ="code"
                    label="Code"
                    placeholder="GI"
                    required
                    v-model:valeur="form.code"
                    :error="form.errors.code"
                />

                <div class="col-span-2">
                    <ChampChaine
                        :mode_vue="mode_vue"
                        nom_champ="nom"
                        label="Nom"
                        placeholder="Génie Informatique"
                        required
                        v-model:valeur="form.nom"
                        :error="form.errors.nom"
                    />
                </div>
            </div>

            <ChampTexte
                :mode_vue="mode_vue"
                nom_champ="description"
                label="Description"
                v-model:valeur="form.description"
                :error="form.errors.description"
            />

            <!--=================================================================================================-->
            <!-- Boutons (create / edit uniquement) -->
            <!--=================================================================================================-->
            <div v-if="is_editable" class="flex gap-2">
                <Button type="submit" :disabled="form.processing"
                    >Enregistrer</Button
                >

                <Button as-child variant="outline">
                    <Link :href="url_annuler">Annuler</Link>
                </Button>
            </div>
        </form>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import ChampChaine from '@/_core/renders/champ-chaine.vue';
import ChampTexte from '@/_core/renders/champ-texte.vue';
import { renders, type ModeVue } from '@/_core/renders';
import {supprimer_avec_confirmation} from "@/_core/dialogs/actions";

interface Filiere {
    cle: string | null;
    code: string | null;
    nom: string | null;
    description: string | null;
    can_be_deleted: boolean;
}

interface FiliereDetailInterface {
    mode_vue: ModeVue;
    titre_page: string;
    item: Filiere;
}

const props = defineProps<FiliereDetailInterface>();

//==============================================================================================================
// Formulaire
//==============================================================================================================
const form = useForm({
    code: props.item.code ?? '',
    nom: props.item.nom ?? '',
    description: props.item.description ?? '',
});

const is_editable = computed(
    () =>
        props.mode_vue === renders.mode_create ||
        props.mode_vue === renders.mode_edit,
);

//==============================================================================================================
// URLs
//==============================================================================================================
const url_list = '/filieres';
const url_detail = props.item.cle ? `/filiere/${props.item.cle}` : '/filiere';
const url_annuler =
    props.mode_vue === renders.mode_edit ? url_detail : url_list;

//==============================================================================================================
// Actions
//==============================================================================================================
const enregistrer = () => form.post(url_detail, { preserveState: 'errors' });

const supprimer = () =>
    supprimer_avec_confirmation(
        url_detail,
        `Supprimer la filière ${props.item.code} ?`,
        "Elle sera archivée et n'apparaîtra plus dans les listes.",
    );
</script>
