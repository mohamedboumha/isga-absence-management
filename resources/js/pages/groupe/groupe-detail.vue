<template>
    <Head :title="titre_page"/>

    <div class="flex max-w-2xl flex-col gap-6 p-4">
        <!--=====================================================================================================-->
        <!-- En-tête -->
        <!--=====================================================================================================-->
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold">{{ titre_page }}</h1>

            <div v-if="mode_vue === renders.mode_consultation" class="flex gap-2">
                <Button as-child variant="outline">
                    <Link :href="`${url_detail}/edit`">Modifier</Link>
                </Button>

                <Button v-if="item.can_be_deleted" variant="destructive" @click="supprimer">Supprimer</Button>
            </div>
        </div>

        <!--=====================================================================================================-->
        <!-- Champs -->
        <!--=====================================================================================================-->
        <form class="grid gap-4" @submit.prevent="enregistrer">
            <ChampSelect
                :mode_vue="mode_vue"
                nom_champ="annee_universitaire_id"
                label="Année universitaire"
                placeholder="Choisir une année"
                required
                :options="annees"
                v-model:valeur="form.annee_universitaire_id"
                :error="form.errors.annee_universitaire_id"
            />

            <div class="grid grid-cols-3 gap-4">
                <div class="col-span-2">
                    <ChampSelect
                        :mode_vue="mode_vue"
                        nom_champ="filiere_id"
                        label="Filière"
                        placeholder="Choisir une filière"
                        required
                        :options="filieres"
                        v-model:valeur="form.filiere_id"
                        :error="form.errors.filiere_id"
                    />
                </div>

                <ChampSelect
                    :mode_vue="mode_vue"
                    nom_champ="niveau"
                    label="Niveau"
                    placeholder="Niveau"
                    required
                    :options="niveaux"
                    v-model:valeur="form.niveau"
                    :error="form.errors.niveau"
                />
            </div>

            <ChampChaine
                :mode_vue="mode_vue"
                nom_champ="nom"
                label="Nom du groupe"
                placeholder="GI-L3-A"
                required
                v-model:valeur="form.nom"
                :error="form.errors.nom"
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
    </div>
</template>


<script setup lang="ts">
import {computed} from 'vue';
import {Head, Link, router, useForm} from '@inertiajs/vue3';
import {Button} from '@/components/ui/button';
import ChampChaine from '@/_core/renders/champ-chaine.vue';
import ChampSelect from '@/_core/renders/champ-select.vue';
import {renders, type ModeVue} from '@/_core/renders';
import type {SelectOption} from '@/_core/renders/types';

interface Groupe {
    cle: string | null;
    annee_universitaire_id: number | null;
    filiere_id: number | null;
    niveau: string | null;
    nom: string | null;
    can_be_deleted: boolean;
}

interface GroupeDetailInterface {
    mode_vue: ModeVue;
    titre_page: string;
    item: Groupe;
    annees: SelectOption[];
    filieres: SelectOption[];
    niveaux: SelectOption[];
}

const props = defineProps<GroupeDetailInterface>();

//==============================================================================================================
// Formulaire
//==============================================================================================================
const form = useForm({
    annee_universitaire_id: props.item.annee_universitaire_id ?? null,
    filiere_id: props.item.filiere_id ?? null,
    niveau: props.item.niveau ?? null,
    nom: props.item.nom ?? '',
});

const is_editable = computed(() => props.mode_vue === renders.mode_create || props.mode_vue === renders.mode_edit);

//==============================================================================================================
// URLs
//==============================================================================================================
const url_list = '/groupes';
const url_detail = props.item.cle ? `/groupe/${props.item.cle}` : '/groupe';
const url_annuler = props.mode_vue === renders.mode_edit ? url_detail : url_list;

//==============================================================================================================
// Actions
//==============================================================================================================
const enregistrer = () => form.post(url_detail, {preserveState: 'errors'});

const supprimer = () => {
    if (!confirm(`Supprimer le groupe ${props.item.nom} ?`)) return;

    router.delete(url_detail, {
        onError: (errors) => alert(errors.groupe),
    });
};
</script>
