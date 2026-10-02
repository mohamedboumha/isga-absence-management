<template>
    <Head :title="titre_page"/>

    <div class="flex flex-col gap-6 p-4">
        <!--=====================================================================================================-->
        <!-- En-tête -->
        <!--=====================================================================================================-->
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex flex-col gap-2">
                <h1 class="text-xl font-semibold">{{ titre_page }}</h1>

                <div v-if="item.cle" class="flex flex-wrap items-center gap-2 text-sm">
                    <BadgeCouleur v-if="item.couleur" :couleur="item.couleur" :label="item.code ?? ''"/>
                    <span v-if="item.couleur_source" class="text-muted-foreground">Couleur {{
                            item.couleur_source
                        }}</span>
                </div>
            </div>

            <div v-if="mode_vue === renders.mode_consultation" class="flex flex-wrap gap-2">
                <Button as-child variant="outline">
                    <Link :href="`${url_detail}/edit`">Modifier</Link>
                </Button>

                <Button v-if="item.can_be_deleted" variant="destructive" @click="supprimer">Supprimer</Button>
            </div>
        </div>

        <form class="flex flex-col gap-6" @submit.prevent="enregistrer">
            <!--=================================================================================================-->
            <!-- Informations -->
            <!--=================================================================================================-->
            <CarteSection titre="Informations">
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

                    <ChampSelect
                        :mode_vue="mode_vue"
                        nom_champ="filiere_id"
                        label="Filière (spécialité)"
                        :options="filieres_du_cycle"
                        v-model:valeur="form.filiere_id"
                        :error="form.errors.filiere_id"
                    />

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

                    <ChampChaine :mode_vue="mode_vue" nom_champ="code" label="Code" placeholder="3CI-IABD" required
                                 v-model:valeur="form.code" :error="form.errors.code"/>

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
            </CarteSection>

            <!--=================================================================================================-->
            <!-- Année suivante : champ modifiable (création, modification) -->
            <!--=================================================================================================-->
            <CarteSection v-if="is_editable" titre="Année suivante">
                <p v-if="est_derniere_annee" class="text-muted-foreground text-sm">Dernière année du cycle : les
                    étudiants admis obtiennent leur diplôme.</p>

                <template v-else>
                    <p class="text-muted-foreground mb-4 text-sm">
                        Niveaux que peuvent rejoindre les étudiants admis. S'il y en a plusieurs, la spécialité est
                        choisie au passage d'année.
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
            </CarteSection>

            <div v-if="is_editable" class="flex gap-2">
                <Button type="submit" :disabled="form.processing">Enregistrer</Button>
                <Button as-child variant="outline">
                    <Link :href="url_annuler">Annuler</Link>
                </Button>
            </div>
        </form>

        <!--=====================================================================================================-->
        <!-- Consultation : sections liées à gauche, "En bref" à droite -->
        <!--=====================================================================================================-->
        <div v-if="consultation" class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
            <div class="flex min-w-0 flex-col gap-6">
                <!--=============================================================================================-->
                <!-- Groupes de l'année -->
                <!--=============================================================================================-->
                <CarteSection
                    titre="Groupes cette année"
                    :sous_titre="consultation.resume.annee"
                    :compteur="consultation.groupes.length"
                    :lien="{ url: consultation.liens.groupes, label: 'Voir dans la liste' }"
                    :avec_marges="false"
                >
                    <Link
                        v-for="groupe in consultation.groupes"
                        :key="groupe.url"
                        :href="groupe.url"
                        class="hover:bg-muted/50 flex items-center justify-between gap-3 border-b px-5 py-3 text-sm last:border-0"
                    >
                        <BadgeCouleur :couleur="item.couleur ?? '#475569'" :label="groupe.nom"/>
                        <span class="text-muted-foreground tabular-nums">{{ groupe.effectif }} étudiant(s)</span>
                    </Link>

                    <p v-if="!consultation.groupes.length" class="text-muted-foreground px-5 py-8 text-center text-sm">
                        Aucun groupe de ce niveau cette année.</p>
                </CarteSection>

                <!--=============================================================================================-->
                <!-- Modules par semestre -->
                <!--=============================================================================================-->
                <CarteSection
                    titre="Modules par semestre"
                    :compteur="consultation.resume.nb_modules"
                    :lien="{ url: consultation.liens.modules, label: 'Voir dans la liste' }"
                    :avec_marges="false"
                >
                    <div v-for="semestre in consultation.semestres" :key="semestre.numero"
                         class="border-b last:border-0">
                        <p class="text-muted-foreground bg-muted/40 flex justify-between px-5 py-2 text-xs font-medium">
                            <span>Semestre {{ semestre.numero }}</span>
                            <span class="tabular-nums">{{ semestre.volume }} h</span>
                        </p>

                        <Link
                            v-for="module in semestre.modules"
                            :key="module.url"
                            :href="module.url"
                            class="hover:bg-muted/50 flex items-center gap-3 border-t px-5 py-3 text-sm first-of-type:border-t-0"
                        >
                            <BadgeCouleur :couleur="module.couleur" :label="module.code"/>
                            <span class="min-w-0 flex-1 truncate">{{ module.intitule }}</span>
                            <span class="text-muted-foreground shrink-0 tabular-nums">{{ module.volume }} h</span>
                        </Link>

                        <p v-if="!semestre.modules.length" class="text-muted-foreground px-5 py-4 text-sm">Aucun module
                            ce semestre.</p>
                    </div>
                </CarteSection>

                <!--=============================================================================================-->
                <!-- Parcours : d'où viennent les étudiants, où ils vont ensuite -->
                <!--=============================================================================================-->
                <CarteSection titre="Parcours">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="flex flex-col gap-2">
                            <span class="text-muted-foreground text-xs">On y accède depuis</span>

                            <div v-if="consultation.parcours.precedents.length" class="flex flex-wrap gap-2">
                                <Link v-for="precedent in consultation.parcours.precedents" :key="precedent.url"
                                      :href="precedent.url" :title="precedent.libelle">
                                    <BadgeCouleur :couleur="precedent.couleur" :label="precedent.code"/>
                                </Link>
                            </div>
                            <span v-else class="text-sm">Première année (entrée dans le cycle)</span>
                        </div>

                        <div class="flex flex-col gap-2">
                            <span class="text-muted-foreground text-xs">Année suivante</span>

                            <span v-if="consultation.parcours.est_derniere_annee" class="text-sm">Diplôme (dernière année du cycle)</span>

                            <div v-else-if="consultation.parcours.suivants.length" class="flex flex-wrap gap-2">
                                <Link v-for="suivant in consultation.parcours.suivants" :key="suivant.url"
                                      :href="suivant.url" :title="suivant.libelle">
                                    <BadgeCouleur :couleur="suivant.couleur" :label="suivant.code"/>
                                </Link>
                            </div>

                            <span v-else class="text-attente text-sm">Aucun niveau suivant défini : à compléter avant le passage d'année.</span>
                        </div>
                    </div>
                </CarteSection>
            </div>

            <!--=================================================================================================-->
            <!-- En bref : reste visible au défilement -->
            <!--=================================================================================================-->
            <aside class="order-first lg:sticky lg:top-4 lg:order-none">
                <CarteSection titre="En bref" :sous_titre="consultation.resume.cycle">
                    <div class="grid grid-cols-2 gap-4">
                        <Indicateur label="Groupes" :valeur="consultation.resume.nb_groupes" detail="cette année"/>
                        <Indicateur label="Étudiants" :valeur="consultation.resume.effectif" detail="cette année"/>
                        <Indicateur label="Modules" :valeur="consultation.resume.nb_modules"/>
                        <Indicateur label="Volume total" :valeur="consultation.resume.volume_total" unite="h"/>
                    </div>
                </CarteSection>
            </aside>
        </div>
    </div>
</template>


<script setup lang="ts">
import {computed, watch} from 'vue';
import {Head, Link, useForm} from '@inertiajs/vue3';
import {toast} from 'vue-sonner';
import {Button} from '@/components/ui/button';
import CarteSection from '@/_core/detail/carte-section.vue';
import Indicateur from '@/_core/detail/indicateur.vue';
import {supprimer_avec_confirmation} from '@/_core/dialogs/actions';
import BadgeCouleur from '@/_core/renders/badge-couleur.vue';
import ChampChaine from '@/_core/renders/champ-chaine.vue';
import ChampMultiSelect from '@/_core/renders/champ-multi-select.vue';
import ChampNombre from '@/_core/renders/champ-nombre.vue';
import ChampSelect from '@/_core/renders/champ-select.vue';
import {renders, type ModeVue} from '@/_core/renders';
import type {SelectOption} from '@/_core/renders/types';

interface NiveauEtude {
    cle: string | null;
    cycle_id: number | null;
    filiere_id: number | null;
    annee_cycle: number | null;
    code: string | null;
    libelle: string | null;
    nb_semestres: number | null;
    couleur: string | null;
    couleur_source: string | null;
    suivants: number[];
    precedents: string[];
    est_derniere_annee: boolean;
    can_be_deleted: boolean;
}

interface CycleOption extends SelectOption {
    nb_annees: number;
    couleur: string;
}

interface FiliereOption extends SelectOption {
    cycle_id: number;
}

interface NiveauOption extends SelectOption {
    cycle_id: number;
    annee_cycle: number;
}

interface BadgeNiveau {
    code: string;
    libelle: string;
    couleur: string;
    url: string;
}

interface ConsultationNiveau {
    resume: {
        cycle: string;
        annee: string | null;
        nb_groupes: number;
        effectif: number;
        nb_modules: number;
        volume_total: number;
    };
    groupes: { nom: string; effectif: number; url: string }[];
    semestres: {
        numero: number;
        volume: number;
        modules: { code: string; intitule: string; volume: number; couleur: string; url: string }[];
    }[];
    parcours: {
        precedents: BadgeNiveau[];
        suivants: BadgeNiveau[];
        est_derniere_annee: boolean;
    };
    liens: {
        groupes: string;
        modules: string;
    };
}

interface NiveauEtudeDetailInterface {
    mode_vue: ModeVue;
    titre_page: string;
    item: NiveauEtude;
    cycles: CycleOption[];
    filieres: FiliereOption[];
    niveaux: NiveauOption[];
    consultation: ConsultationNiveau | null;
}

const props = defineProps<NiveauEtudeDetailInterface>();

const form = useForm({
    cycle_id: props.item.cycle_id ?? null,
    filiere_id: props.item.filiere_id ?? '',
    annee_cycle: props.item.annee_cycle ?? 1,
    code: props.item.code ?? '',
    libelle: props.item.libelle ?? '',
    nb_semestres: props.item.nb_semestres ?? 2,
    suivants: props.item.suivants ?? [],
});

const is_editable = computed(() => props.mode_vue === renders.mode_create || props.mode_vue === renders.mode_edit);

//==============================================================================================================
// Selon le cycle choisi : ses filières, sa durée, et les niveaux de l'année suivante
//==============================================================================================================
const cycle_choisi = computed(() => props.cycles.find((cycle) => cycle.valeur === form.cycle_id));

const filieres_du_cycle = computed(() => [
    {valeur: '', label: 'Aucune — tronc commun'},
    ...props.filieres.filter((filiere) => filiere.cycle_id === form.cycle_id),
]);

const est_derniere_annee = computed(() => Boolean(cycle_choisi.value) && Number(form.annee_cycle) >= (cycle_choisi.value?.nb_annees ?? 0));

const niveaux_suivants_possibles = computed(() =>
    props.niveaux.filter((niveau) => niveau.cycle_id === form.cycle_id && niveau.annee_cycle === Number(form.annee_cycle) + 1),
);

//==============================================================================================================
// Changement de cycle ou d'année : on retire ce qui ne correspond plus
//==============================================================================================================
watch(
    () => [form.cycle_id, form.annee_cycle],
    () => {
        if (!is_editable.value) return;

        if (!filieres_du_cycle.value.some((filiere) => filiere.valeur === form.filiere_id)) {
            form.filiere_id = '';
        }

        const possibles = niveaux_suivants_possibles.value.map((niveau) => niveau.valeur);
        form.suivants = form.suivants.filter((id) => possibles.includes(id));
    },
);

const url_list = '/niveaux-etudes';
const url_detail = props.item.cle ? `/niveau-etude/${props.item.cle}` : '/niveau-etude';
const url_annuler = props.mode_vue === renders.mode_edit ? url_detail : url_list;

const enregistrer = () =>
    form.post(url_detail, {
        preserveState: 'errors',
        onError: () => toast.error('Certains champs sont à corriger.'),
    });

const supprimer = () =>
    supprimer_avec_confirmation(url_detail, `Supprimer le niveau ${props.item.code} ?`, 'Il sera archivé, ainsi que son parcours.');
</script>
