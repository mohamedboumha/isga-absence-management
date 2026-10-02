<template>
    <Head :title="titre_page"/>

    <div class="flex flex-col gap-6 p-4">
        <!--=====================================================================================================-->
        <!-- En-tête -->
        <!--=====================================================================================================-->
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex flex-col gap-2">
                <h1 class="text-xl font-semibold">{{ titre_page }}</h1>

                <div v-if="consultation" class="flex flex-wrap items-center gap-2 text-sm">
                    <BadgeCouleur v-if="couleur_effective" :couleur="couleur_effective" :label="item.code ?? ''"/>
                    <span class="text-muted-foreground">dans le</span>
                    <Link :href="consultation.resume.cycle.url">
                        <BadgeCouleur :couleur="consultation.resume.cycle.couleur"
                                      :label="consultation.resume.cycle.code"/>
                    </Link>
                </div>
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
                        <ChampTexte :mode_vue="mode_vue" nom_champ="description" label="Description"
                                    v-model:valeur="form.description" :error="form.errors.description"/>
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

        <!--=====================================================================================================-->
        <!-- Consultation : sections liées à gauche, "En bref" à droite -->
        <!--=====================================================================================================-->
        <div v-if="consultation" class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
            <div class="flex min-w-0 flex-col gap-6">
                <!--=============================================================================================-->
                <!-- Niveaux de la filière, par année du cycle -->
                <!--=============================================================================================-->
                <CarteSection
                    titre="Niveaux de la filière"
                    :sous_titre="consultation.resume.annee ? `Groupes et étudiants en ${consultation.resume.annee}` : null"
                    :compteur="consultation.resume.nb_niveaux"
                    :avec_marges="false"
                >
                    <StructureNiveaux v-if="consultation.structure.length" :structure="consultation.structure"/>

                    <p v-else class="text-muted-foreground px-5 py-8 text-center text-sm">
                        Aucun niveau d'études n'utilise encore cette filière.
                    </p>
                </CarteSection>

                <!--=============================================================================================-->
                <!-- Groupes de l'année -->
                <!--=============================================================================================-->
                <CarteSection titre="Groupes cette année" :compteur="consultation.groupes.length" :avec_marges="false">
                    <Link
                        v-for="groupe in consultation.groupes"
                        :key="groupe.url"
                        :href="groupe.url"
                        class="hover:bg-muted/50 flex items-center justify-between gap-3 border-b px-5 py-3 text-sm last:border-0"
                    >
                        <BadgeCouleur :couleur="groupe.couleur" :label="groupe.nom"/>
                        <span class="text-muted-foreground tabular-nums">{{ groupe.effectif }} étudiant(s)</span>
                    </Link>

                    <p v-if="!consultation.groupes.length" class="text-muted-foreground px-5 py-8 text-center text-sm">
                        Aucun groupe de cette filière cette année.</p>
                </CarteSection>
            </div>

            <!--=================================================================================================-->
            <!-- En bref : reste visible au défilement -->
            <!--=================================================================================================-->
            <aside class="order-first lg:sticky lg:top-4 lg:order-none">
                <CarteSection titre="En bref"
                              :sous_titre="consultation.resume.annee ? `Année ${consultation.resume.annee}` : null">
                    <div class="flex flex-col gap-5">
                        <Indicateur
                            label="Taux d'absence"
                            :valeur="formater_nombre(consultation.resume.taux)"
                            unite="%"
                            :detail="`${consultation.resume.nb_absences} absence(s) cette année`"
                            :tonalite="tonalite_taux(consultation.resume.taux)"
                        />

                        <div class="grid grid-cols-2 gap-4">
                            <Indicateur label="Niveaux" :valeur="consultation.resume.nb_niveaux"/>
                            <Indicateur label="Groupes" :valeur="consultation.resume.nb_groupes" detail="cette année"/>
                            <Indicateur label="Étudiants" :valeur="consultation.resume.effectif" detail="cette année"/>
                        </div>
                    </div>
                </CarteSection>
            </aside>
        </div>
    </div>
</template>


<script setup lang="ts">
import {computed} from 'vue';
import {Head, Link, useForm} from '@inertiajs/vue3';
import {toast} from 'vue-sonner';
import {Button} from '@/components/ui/button';
import CarteSection from '@/_core/detail/carte-section.vue';
import Indicateur from '@/_core/detail/indicateur.vue';
import StructureNiveaux from '@/_core/detail/structure-niveaux.vue';
import {formater_nombre, tonalite_taux} from '@/_core/detail/taux';
import type {AnneeStructure, GroupeResume} from '@/_core/detail/types';
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

interface ConsultationFiliere {
    resume: {
        annee: string | null;
        cycle: { code: string; nom: string; couleur: string; url: string };
        nb_niveaux: number;
        nb_groupes: number;
        effectif: number;
        nb_absences: number;
        taux: number;
    };
    structure: AnneeStructure[];
    groupes: GroupeResume[];
}

interface FiliereDetailInterface {
    mode_vue: ModeVue;
    titre_page: string;
    item: Filiere;
    cycles: CycleOption[];
    consultation: ConsultationFiliere | null;
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
