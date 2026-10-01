<template>
    <Head :title="titre_page" />

    <div class="flex max-w-3xl flex-col gap-6 p-4">
        <!--=====================================================================================================-->
        <!-- En-tête -->
        <!--=====================================================================================================-->
        <div class="flex items-start justify-between gap-4">
            <div class="flex flex-col gap-1">
                <h1 class="text-xl font-semibold">{{ titre_page }}</h1>
                <p
                    v-if="item.precedents.length"
                    class="text-sm text-muted-foreground"
                >
                    On y accède depuis : {{ item.precedents.join(', ') }}
                </p>
            </div>

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
                <div class="col-span-2">
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
                </div>

                <ChampNombre
                    :mode_vue="mode_vue"
                    nom_champ="annee_cycle"
                    label="Année du cycle"
                    required
                    :min="1"
                    :max="cycle_choisi?.nb_annees"
                    v-model:valeur="form.annee_cycle"
                    :error="form.errors.annee_cycle"
                />
            </div>

            <ChampSelect
                :mode_vue="mode_vue"
                nom_champ="filiere_id"
                label="Filière (spécialité)"
                :options="filieres_du_cycle"
                v-model:valeur="form.filiere_id"
                :error="form.errors.filiere_id"
            />

            <div class="grid grid-cols-3 gap-4">
                <ChampChaine
                    :mode_vue="mode_vue"
                    nom_champ="code"
                    label="Code"
                    placeholder="3CI-IABD"
                    required
                    v-model:valeur="form.code"
                    :error="form.errors.code"
                />

                <div class="col-span-2">
                    <ChampChaine
                        :mode_vue="mode_vue"
                        nom_champ="libelle"
                        label="Libellé"
                        placeholder="3ème année cycle ingénieur — IABD"
                        required
                        v-model:valeur="form.libelle"
                        :error="form.errors.libelle"
                    />
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <ChampNombre
                    :mode_vue="mode_vue"
                    nom_champ="nb_semestres"
                    label="Semestres dans l'année"
                    required
                    :min="1"
                    :max="4"
                    v-model:valeur="form.nb_semestres"
                    :error="form.errors.nb_semestres"
                />
            </div>

            <!--=================================================================================================-->
            <!-- Parcours -->
            <!--=================================================================================================-->
            <div class="rounded-lg border p-4">
                <h2 class="mb-1 font-medium">Année suivante</h2>

                <p
                    v-if="est_derniere_annee"
                    class="text-sm text-muted-foreground"
                >
                    Dernière année du cycle : les étudiants admis obtiennent
                    leur diplôme.
                </p>

                <template v-else>
                    <p class="mb-3 text-sm text-muted-foreground">
                        Niveaux que peuvent rejoindre les étudiants admis. S'il
                        y en a plusieurs, la spécialité est choisie au passage
                        d'année.
                    </p>

                    <ChampMultiSelect
                        :mode_vue="mode_vue"
                        nom_champ="suivants"
                        label="Niveaux suivants"
                        :options="niveaux_suivants_possibles"
                        v-model:valeur="form.suivants"
                        :error="form.errors.suivants"
                    />
                </template>
            </div>

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
import { computed, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import { supprimer_avec_confirmation } from '@/_core/dialogs/actions';
import ChampChaine from '@/_core/renders/champ-chaine.vue';
import ChampMultiSelect from '@/_core/renders/champ-multi-select.vue';
import ChampNombre from '@/_core/renders/champ-nombre.vue';
import ChampSelect from '@/_core/renders/champ-select.vue';
import { renders, type ModeVue } from '@/_core/renders';
import type { SelectOption } from '@/_core/renders/types';

interface NiveauEtude {
    cle: string | null;
    cycle_id: number | null;
    filiere_id: number | null;
    annee_cycle: number | null;
    code: string | null;
    libelle: string | null;
    nb_semestres: number | null;
    suivants: number[];
    precedents: string[];
    est_derniere_annee: boolean;
    can_be_deleted: boolean;
}

interface CycleOption extends SelectOption {
    nb_annees: number;
}

interface FiliereOption extends SelectOption {
    cycle_id: number;
}

interface NiveauOption extends SelectOption {
    cycle_id: number;
    annee_cycle: number;
}

interface NiveauEtudeDetailInterface {
    mode_vue: ModeVue;
    titre_page: string;
    item: NiveauEtude;
    cycles: CycleOption[];
    filieres: FiliereOption[];
    niveaux: NiveauOption[];
}

const props = defineProps<NiveauEtudeDetailInterface>();

//==============================================================================================================
// Formulaire
//==============================================================================================================
const form = useForm({
    cycle_id: props.item.cycle_id ?? null,
    filiere_id: props.item.filiere_id ?? '',
    annee_cycle: props.item.annee_cycle ?? 1,
    code: props.item.code ?? '',
    libelle: props.item.libelle ?? '',
    nb_semestres: props.item.nb_semestres ?? 2,
    suivants: props.item.suivants ?? [],
});

const is_editable = computed(
    () =>
        props.mode_vue === renders.mode_create ||
        props.mode_vue === renders.mode_edit,
);

//==============================================================================================================
// Selon le cycle choisi : ses filières, sa durée, et les niveaux de l'année suivante
//==============================================================================================================
const cycle_choisi = computed(() =>
    props.cycles.find((cycle) => cycle.valeur === form.cycle_id),
);

const filieres_du_cycle = computed(() => [
    { valeur: '', label: 'Aucune — tronc commun' },
    ...props.filieres.filter((filiere) => filiere.cycle_id === form.cycle_id),
]);

const est_derniere_annee = computed(
    () =>
        Boolean(cycle_choisi.value) &&
        Number(form.annee_cycle) >= (cycle_choisi.value?.nb_annees ?? 0),
);

const niveaux_suivants_possibles = computed(() =>
    props.niveaux.filter(
        (niveau) =>
            niveau.cycle_id === form.cycle_id &&
            niveau.annee_cycle === Number(form.annee_cycle) + 1,
    ),
);

//==============================================================================================================
// Changement de cycle ou d'année : on retire ce qui ne correspond plus
//==============================================================================================================
watch(
    () => [form.cycle_id, form.annee_cycle],
    () => {
        if (!is_editable.value) return;

        if (
            !filieres_du_cycle.value.some(
                (filiere) => filiere.valeur === form.filiere_id,
            )
        ) {
            form.filiere_id = '';
        }

        const possibles = niveaux_suivants_possibles.value.map(
            (niveau) => niveau.valeur,
        );
        form.suivants = form.suivants.filter((id) => possibles.includes(id));
    },
);

//==============================================================================================================
// URLs et actions
//==============================================================================================================
const url_list = '/niveaux-etudes';
const url_detail = props.item.cle
    ? `/niveau-etude/${props.item.cle}`
    : '/niveau-etude';
const url_annuler =
    props.mode_vue === renders.mode_edit ? url_detail : url_list;

const enregistrer = () =>
    form.post(url_detail, {
        preserveState: 'errors',
        onError: () => toast.error('Certains champs sont à corriger.'),
    });

const supprimer = () =>
    supprimer_avec_confirmation(
        url_detail,
        `Supprimer le niveau ${props.item.code} ?`,
        'Il sera archivé, ainsi que son parcours.',
    );
</script>
