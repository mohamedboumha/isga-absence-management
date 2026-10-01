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
                    <a
                        :href="`${url_detail}/rapport`"
                        target="_blank"
                        rel="noopener"
                        >Rapport PDF</a
                    >
                </Button>

                <Button as-child variant="outline">
                    <Link :href="`${url_detail}/edit`">Modifier</Link>
                </Button>

                <Button
                    v-if="item.can_be_deleted"
                    variant="destructive"
                    @click="supprimer"
                    >Supprimer
                </Button>
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

            <ChampSelect
                :mode_vue="mode_vue"
                nom_champ="niveau_etude_id"
                label="Niveau d'études"
                placeholder="Choisir un niveau"
                required
                :options="niveaux"
                v-model:valeur="form.niveau_etude_id"
                :error="form.errors.niveau_etude_id"
            />

            <ChampChaine
                :mode_vue="mode_vue"
                nom_champ="nom"
                label="Nom du groupe"
                required
                v-model:valeur="form.nom"
                :error="form.errors.nom"
            />

            <!--=================================================================================================-->
            <!-- Boutons (create / edit uniquement) -->
            <!--=================================================================================================-->
            <div v-if="is_editable" class="flex gap-2">
                <Button type="submit" :disabled="form.processing"
                    >Enregistrer
                </Button>

                <Button as-child variant="outline">
                    <Link :href="url_annuler">Annuler</Link>
                </Button>
            </div>
        </form>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import ChampChaine from '@/_core/renders/champ-chaine.vue';
import ChampSelect from '@/_core/renders/champ-select.vue';
import { renders, type ModeVue } from '@/_core/renders';
import type { SelectOption } from '@/_core/renders/types';
import { supprimer_avec_confirmation } from '@/_core/dialogs/actions';

interface Groupe {
    cle: string | null;
    annee_universitaire_id: number | null;
    niveau_etude_id: number | null;
    nom: string | null;
    can_be_deleted: boolean;
}

interface GroupeDetailInterface {
    mode_vue: ModeVue;
    titre_page: string;
    item: Groupe;
    annees: SelectOption[];
    niveaux: SelectOption[];
}

const props = defineProps<GroupeDetailInterface>();

//==============================================================================================================
// Formulaire
//==============================================================================================================
const form = useForm({
    annee_universitaire_id: props.item.annee_universitaire_id ?? null,
    niveau_etude_id: props.item.niveau_etude_id ?? null,
    nom: props.item.nom ?? '',
});

const is_editable = computed(
    () =>
        props.mode_vue === renders.mode_create ||
        props.mode_vue === renders.mode_edit,
);

//==============================================================================================================
// URLs
//==============================================================================================================
const url_list = '/groupes';
const url_detail = props.item.cle ? `/groupe/${props.item.cle}` : '/groupe';
const url_annuler =
    props.mode_vue === renders.mode_edit ? url_detail : url_list;

//==============================================================================================================
// Actions
//==============================================================================================================
const enregistrer = () => form.post(url_detail, { preserveState: 'errors' });

const supprimer = () =>
    supprimer_avec_confirmation(
        url_detail,
        `Supprimer le groupe ${props.item.nom} ?`,
        "Il sera archivé et n'apparaîtra plus dans les listes.",
    );
</script>
