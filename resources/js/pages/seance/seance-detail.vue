<template>
    <Head :title="titre_page"/>

    <div class="flex max-w-3xl flex-col gap-6 p-4">
        <!--=====================================================================================================-->
        <!-- En-tête -->
        <!--=====================================================================================================-->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold">{{ titre_page }}</h1>

                <p v-if="mode_vue === renders.mode_consultation && item.annulee"
                   class="text-destructive text-sm font-medium">
                    Séance annulée
                </p>
            </div>

            <div v-if="mode_vue === renders.mode_consultation" class="flex gap-2">
                <Button v-if="!item.annulee" as-child>
                    <Link :href="`${url_detail}/appel`">Faire l'appel</Link>
                </Button>

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
                nom_champ="groupe_id"
                label="Groupe"
                placeholder="Choisir un groupe"
                required
                :options="groupes"
                v-model:valeur="form.groupe_id"
                :error="form.errors.groupe_id"
            />

            <div class="grid grid-cols-3 gap-4">
                <div class="col-span-2">
                    <ChampSelect
                        :mode_vue="mode_vue"
                        nom_champ="module_id"
                        label="Module"
                        placeholder="Choisir un module"
                        required
                        :options="modules"
                        v-model:valeur="form.module_id"
                        :error="form.errors.module_id"
                    />
                </div>

                <ChampSelect
                    :mode_vue="mode_vue"
                    nom_champ="type"
                    label="Type"
                    required
                    :options="types"
                    v-model:valeur="form.type"
                    :error="form.errors.type"
                />
            </div>

            <ChampSelect
                :mode_vue="mode_vue"
                nom_champ="enseignant_id"
                label="Enseignant"
                :placeholder="form.module_id ? 'Choisir un enseignant' : 'Choisir d\'abord un module'"
                required
                :options="enseignants_du_module"
                v-model:valeur="form.enseignant_id"
                :error="form.errors.enseignant_id"
            />

            <div class="grid grid-cols-4 gap-4">
                <div class="col-span-2">
                    <ChampDate
                        :mode_vue="mode_vue"
                        nom_champ="date"
                        label="Date"
                        required
                        v-model:valeur="form.date"
                        :error="form.errors.date"
                    />
                </div>

                <ChampHeure
                    :mode_vue="mode_vue"
                    nom_champ="heure_debut"
                    label="Début"
                    required
                    v-model:valeur="form.heure_debut"
                    :error="form.errors.heure_debut"
                />

                <ChampHeure
                    :mode_vue="mode_vue"
                    nom_champ="heure_fin"
                    label="Fin"
                    required
                    v-model:valeur="form.heure_fin"
                    :error="form.errors.heure_fin"
                />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <ChampChaine
                    :mode_vue="mode_vue"
                    nom_champ="salle"
                    label="Salle"
                    placeholder="B204"
                    v-model:valeur="form.salle"
                    :error="form.errors.salle"
                />
            </div>

            <ChampBoolean
                v-if="mode_vue !== renders.mode_create"
                :mode_vue="mode_vue"
                nom_champ="annulee"
                label="Séance annulée"
                v-model:valeur="form.annulee"
                :error="form.errors.annulee"
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
import {computed, watch} from 'vue';
import {Head, Link, router, useForm} from '@inertiajs/vue3';
import {Button} from '@/components/ui/button';
import ChampBoolean from '@/_core/renders/champ-boolean.vue';
import ChampChaine from '@/_core/renders/champ-chaine.vue';
import ChampDate from '@/_core/renders/champ-date.vue';
import ChampHeure from '@/_core/renders/champ-heure.vue';
import ChampSelect from '@/_core/renders/champ-select.vue';
import {renders, type ModeVue} from '@/_core/renders';
import type {SelectOption} from '@/_core/renders/types';

interface Seance {
    cle: string | null;
    module_id: number | null;
    enseignant_id: number | null;
    groupe_id: number | null;
    date: string | null;
    heure_debut: string | null;
    heure_fin: string | null;
    type: string | null;
    salle: string | null;
    annulee: boolean;
    can_be_deleted: boolean;
}

interface ModuleOption extends SelectOption {
    enseignant_ids: number[];
}

interface SeanceDetailInterface {
    mode_vue: ModeVue;
    titre_page: string;
    item: Seance;
    modules: ModuleOption[];
    enseignants: SelectOption[];
    groupes: SelectOption[];
    types: SelectOption[];
}

const props = defineProps<SeanceDetailInterface>();

//==============================================================================================================
// Formulaire
//==============================================================================================================
const form = useForm({
    module_id: props.item.module_id ?? null,
    enseignant_id: props.item.enseignant_id ?? null,
    groupe_id: props.item.groupe_id ?? null,
    date: props.item.date ?? '',
    heure_debut: props.item.heure_debut ?? '',
    heure_fin: props.item.heure_fin ?? '',
    type: props.item.type ?? null,
    salle: props.item.salle ?? '',
    annulee: props.item.annulee ?? false,
});

const is_editable = computed(() => props.mode_vue === renders.mode_create || props.mode_vue === renders.mode_edit);

//==============================================================================================================
// Enseignants du module choisi (en consultation : tous, pour afficher le nom)
//==============================================================================================================
const enseignants_du_module = computed(() => {
    if (!is_editable.value) return props.enseignants;

    const module = props.modules.find((option) => option.valeur === form.module_id);

    if (!module) return [];

    return props.enseignants.filter((option) => module.enseignant_ids.includes(Number(option.valeur)));
});

//==============================================================================================================
// Changement de module : on vide l'enseignant s'il n'enseigne pas le nouveau module
//==============================================================================================================
watch(
    () => form.module_id,
    () => {
        if (!enseignants_du_module.value.some((option) => option.valeur === form.enseignant_id)) {
            form.enseignant_id = null;
        }
    },
);

//==============================================================================================================
// URLs
//==============================================================================================================
const url_list = '/seances';
const url_detail = props.item.cle ? `/seance/${props.item.cle}` : '/seance';
const url_annuler = props.mode_vue === renders.mode_edit ? url_detail : url_list;

//==============================================================================================================
// Actions
//==============================================================================================================
const enregistrer = () => form.post(url_detail, {preserveState: 'errors'});

const supprimer = () => {
    if (!confirm('Supprimer cette séance ?')) return;

    router.delete(url_detail, {
        onError: (errors) => alert(errors.seance),
    });
};
</script>
