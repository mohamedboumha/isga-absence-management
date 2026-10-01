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
                nom_champ="niveau_etude_id"
                label="Niveau d'études"
                placeholder="Choisir un niveau"
                required
                :options="niveaux"
                v-model:valeur="form.niveau_etude_id"
                :error="form.errors.niveau_etude_id"
            />

            <div class="grid grid-cols-3 gap-4">
                <ChampChaine
                    :mode_vue="mode_vue"
                    nom_champ="code"
                    label="Code"
                    placeholder="GI-BDD"
                    required
                    v-model:valeur="form.code"
                    :error="form.errors.code"
                />

                <div class="col-span-2">
                    <ChampChaine
                        :mode_vue="mode_vue"
                        nom_champ="intitule"
                        label="Intitulé"
                        placeholder="Bases de données"
                        required
                        v-model:valeur="form.intitule"
                        :error="form.errors.intitule"
                    />
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <ChampSelect
                    :mode_vue="mode_vue"
                    nom_champ="semestre"
                    label="Semestre"
                    :placeholder="
                        form.niveau_etude_id
                            ? 'Choisir un semestre'
                            : 'Choisir d\'abord un niveau'
                    "
                    required
                    :options="semestres_du_niveau"
                    v-model:valeur="form.semestre"
                    :error="form.errors.semestre"
                />

                <ChampNombre
                    :mode_vue="mode_vue"
                    nom_champ="volume_horaire"
                    label="Volume horaire"
                    placeholder="30"
                    suffixe="h"
                    required
                    :min="1"
                    :max="300"
                    v-model:valeur="form.volume_horaire"
                    :error="form.errors.volume_horaire"
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
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import ChampChaine from '@/_core/renders/champ-chaine.vue';
import ChampNombre from '@/_core/renders/champ-nombre.vue';
import ChampSelect from '@/_core/renders/champ-select.vue';
import { renders, type ModeVue } from '@/_core/renders';
import type { SelectOption } from '@/_core/renders/types';
import { supprimer_avec_confirmation } from '@/_core/dialogs/actions';

interface Module {
    cle: string | null;
    niveau_etude_id: number | null;
    code: string | null;
    intitule: string | null;
    semestre: number | null;
    volume_horaire: number | null;
    can_be_deleted: boolean;
}

interface NiveauOption extends SelectOption {
    nb_semestres: number;
}

interface ModuleDetailInterface {
    mode_vue: ModeVue;
    titre_page: string;
    item: Module;
    niveaux: NiveauOption[];
}

const props = defineProps<ModuleDetailInterface>();

//==============================================================================================================
// Formulaire
//==============================================================================================================
const form = useForm({
    niveau_etude_id: props.item.niveau_etude_id ?? null,
    code: props.item.code ?? '',
    intitule: props.item.intitule ?? '',
    semestre: props.item.semestre ?? null,
    volume_horaire: props.item.volume_horaire ?? null,
});

const is_editable = computed(
    () =>
        props.mode_vue === renders.mode_create ||
        props.mode_vue === renders.mode_edit,
);

//==============================================================================================================
// URLs
//==============================================================================================================
const url_list = '/modules';
const url_detail = props.item.cle ? `/module/${props.item.cle}` : '/module';
const url_annuler =
    props.mode_vue === renders.mode_edit ? url_detail : url_list;

//==============================================================================================================
// Actions
//==============================================================================================================
const enregistrer = () => form.post(url_detail, { preserveState: 'errors' });

const supprimer = () =>
    supprimer_avec_confirmation(
        url_detail,
        `Supprimer le module ${props.item.code} ?`,
        'Il sera archivé et ne sera plus proposé lors de la planification des séances.',
    );

//==============================================================================================================
// Semestres proposés : de 1 au nombre de semestres du niveau choisi
//==============================================================================================================
const semestres_du_niveau = computed(() => {
    const niveau = props.niveaux.find(
        (option) => option.valeur === form.niveau_etude_id,
    );

    return Array.from({ length: niveau?.nb_semestres ?? 0 }, (_, index) => ({
        valeur: index + 1,
        label: `Semestre ${index + 1}`,
    }));
});
</script>
