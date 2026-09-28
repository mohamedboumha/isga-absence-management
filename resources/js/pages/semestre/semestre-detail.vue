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

            <ChampChaine
                :mode_vue="mode_vue"
                nom_champ="libelle"
                label="Libellé"
                placeholder="S1"
                required
                v-model:valeur="form.libelle"
                :error="form.errors.libelle"
            />

            <div class="grid grid-cols-2 gap-4">
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
import ChampDate from '@/_core/renders/champ-date.vue';
import ChampSelect, {
    type SelectOption,
} from '@/_core/renders/champ-select.vue';
import { renders, type ModeVue } from '@/_core/renders';

interface Semestre {
    cle: string | null;
    annee_universitaire_id: number | null;
    libelle: string | null;
    date_debut: string | null;
    date_fin: string | null;
    can_be_deleted: boolean;
}

interface SemestreDetailInterfaxe {
    mode_vue: ModeVue;
    titre_page: string;
    item: Semestre;
    annees: SelectOption[];
}

const props = defineProps<SemestreDetailInterfaxe>();

//==============================================================================================================
// Formulaire
//==============================================================================================================
const form = useForm({
    annee_universitaire_id: props.item.annee_universitaire_id ?? null,
    libelle: props.item.libelle ?? '',
    date_debut: props.item.date_debut ?? '',
    date_fin: props.item.date_fin ?? '',
});

const is_editable = computed(
    () =>
        props.mode_vue === renders.mode_create ||
        props.mode_vue === renders.mode_edit,
);

//==============================================================================================================
// URLs
//==============================================================================================================
const url_list = '/semestres';
const url_detail = props.item.cle ? `/semestre/${props.item.cle}` : '/semestre';
const url_annuler =
    props.mode_vue === renders.mode_edit ? url_detail : url_list;

//==============================================================================================================
// Actions
//==============================================================================================================
const enregistrer = () => form.post(url_detail, { preserveState: 'errors' });

const supprimer = () => {
    if (!confirm(`Supprimer le semestre ${props.item.libelle} ?`)) return;

    router.delete(url_detail, {
        onError: (errors) => alert(errors.semestre),
    });
};
</script>
