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
                    <span class="text-muted-foreground">{{ item.nb_annees }} an(s)</span>
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
                    <ChampChaine :mode_vue="mode_vue" nom_champ="code" label="Code" placeholder="ING" required
                                 v-model:valeur="form.code" :error="form.errors.code"/>

                    <ChampChaine :mode_vue="mode_vue" nom_champ="nom" label="Nom" placeholder="Cycle ingénieur" required
                                 v-model:valeur="form.nom" :error="form.errors.nom"/>

                    <ChampNombre
                        :mode_vue="mode_vue"
                        nom_champ="nb_annees"
                        label="Durée"
                        suffixe="an(s)"
                        required
                        :min="1"
                        :max="8"
                        v-model:valeur="form.nb_annees"
                        :error="form.errors.nb_annees"
                    />

                    <ChampCouleur :mode_vue="mode_vue" nom_champ="couleur" label="Couleur" required
                                  v-model:valeur="form.couleur" :error="form.errors.couleur"/>
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
                <!-- Structure du cycle : une ligne par année -->
                <!--=============================================================================================-->
                <CarteSection
                    titre="Structure du cycle"
                    :sous_titre="consultation.resume.annee ? `Groupes et étudiants en ${consultation.resume.annee}` : null"
                    :lien="{ url: consultation.liens.groupes, label: 'Voir les groupes' }"
                    :avec_marges="false"
                >
                    <StructureNiveaux :structure="consultation.structure"/>
                </CarteSection>

                <!--=============================================================================================-->
                <!-- Filières -->
                <!--=============================================================================================-->
                <CarteSection titre="Filières" :compteur="consultation.filieres.length" :avec_marges="false">
                    <Link
                        v-for="filiere in consultation.filieres"
                        :key="filiere.url"
                        :href="filiere.url"
                        class="hover:bg-muted/50 flex items-center gap-3 border-b px-5 py-3 text-sm last:border-0"
                    >
                        <BadgeCouleur :couleur="filiere.couleur" :label="filiere.code"/>
                        <span class="min-w-0 flex-1 truncate">{{ filiere.nom }}</span>
                        <span class="text-muted-foreground shrink-0 tabular-nums">{{
                                filiere.nb_niveaux
                            }} niveau(x)</span>
                    </Link>

                    <p v-if="!consultation.filieres.length" class="text-muted-foreground px-5 py-8 text-center text-sm">
                        Aucune filière : tous les niveaux de ce cycle sont en tronc commun.
                    </p>
                </CarteSection>
            </div>

            <!--=================================================================================================-->
            <!-- En bref : reste visible au défilement -->
            <!--=================================================================================================-->
            <aside class="order-first lg:sticky lg:top-4 lg:order-none">
                <CarteSection titre="En bref"
                              :sous_titre="consultation.resume.annee ? `Année ${consultation.resume.annee}` : null">
                    <div class="flex flex-col gap-5">
                        <div class="grid grid-cols-2 gap-4">
                            <Indicateur label="Durée" :valeur="consultation.resume.nb_annees" unite="an(s)"/>
                            <Indicateur label="Filières" :valeur="consultation.resume.nb_filieres"/>
                            <Indicateur label="Niveaux" :valeur="consultation.resume.nb_niveaux"/>
                            <Indicateur label="Groupes" :valeur="consultation.resume.nb_groupes" detail="cette année"/>
                        </div>

                        <Link :href="consultation.liens.etudiants" class="group flex flex-col gap-0.5">
                            <Indicateur label="Étudiants" :valeur="consultation.resume.effectif" detail="cette année"/>
                            <span
                                class="text-muted-foreground group-hover:text-foreground inline-flex items-center gap-1 text-xs font-medium">
                                Voir la liste
                                <ArrowRight class="size-3.5"/>
                            </span>
                        </Link>
                    </div>
                </CarteSection>
            </aside>
        </div>
    </div>
</template>


<script setup lang="ts">
import {computed} from 'vue';
import {Head, Link, useForm} from '@inertiajs/vue3';
import {ArrowRight} from '@lucide/vue';
import {toast} from 'vue-sonner';
import {Button} from '@/components/ui/button';
import CarteSection from '@/_core/detail/carte-section.vue';
import Indicateur from '@/_core/detail/indicateur.vue';
import StructureNiveaux from '@/_core/detail/structure-niveaux.vue';
import type {AnneeStructure} from '@/_core/detail/types';
import {supprimer_avec_confirmation} from '@/_core/dialogs/actions';
import BadgeCouleur from '@/_core/renders/badge-couleur.vue';
import ChampChaine from '@/_core/renders/champ-chaine.vue';
import ChampCouleur from '@/_core/renders/champ-couleur.vue';
import ChampNombre from '@/_core/renders/champ-nombre.vue';
import {renders, type ModeVue} from '@/_core/renders';

interface Cycle {
    cle: string | null;
    code: string | null;
    nom: string | null;
    nb_annees: number | null;
    couleur: string | null;
    can_be_deleted: boolean;
}

interface ConsultationCycle {
    resume: {
        annee: string | null;
        nb_annees: number;
        nb_filieres: number;
        nb_niveaux: number;
        nb_groupes: number;
        effectif: number;
    };
    structure: AnneeStructure[];
    filieres: { code: string; nom: string; couleur: string; nb_niveaux: number; url: string }[];
    liens: {
        groupes: string;
        etudiants: string;
    };
}

interface CycleDetailInterface {
    mode_vue: ModeVue;
    titre_page: string;
    item: Cycle;
    consultation: ConsultationCycle | null;
}

const props = defineProps<CycleDetailInterface>();

const form = useForm({
    code: props.item.code ?? '',
    nom: props.item.nom ?? '',
    nb_annees: props.item.nb_annees ?? null,
    couleur: props.item.couleur ?? '#475569',
});

const is_editable = computed(() => props.mode_vue === renders.mode_create || props.mode_vue === renders.mode_edit);

const url_list = '/cycles';
const url_detail = props.item.cle ? `/cycle/${props.item.cle}` : '/cycle';
const url_annuler = props.mode_vue === renders.mode_edit ? url_detail : url_list;

const enregistrer = () =>
    form.post(url_detail, {
        preserveState: 'errors',
        onError: () => toast.error('Certains champs sont à corriger.'),
    });

const supprimer = () =>
    supprimer_avec_confirmation(url_detail, `Supprimer le cycle ${props.item.nom} ?`, "Il sera archivé et n'apparaîtra plus dans les listes.");
</script>
