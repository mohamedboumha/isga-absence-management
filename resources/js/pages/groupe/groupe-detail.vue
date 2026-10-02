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
                    <BadgeCouleur :couleur="consultation.resume.couleur" :label="consultation.resume.niveau_code"/>
                    <span class="text-muted-foreground">{{ consultation.resume.annee }}</span>
                    <StatutPill v-if="consultation.resume.annee_active" statut="en_cours" label="Année en cours"/>
                </div>
            </div>

            <div v-if="mode_vue === renders.mode_consultation" class="flex flex-wrap gap-2">
                <BoutonDocument
                    :url="`${url_detail}/rapport`"
                    :titre="`Rapport d'absences du groupe ${item.nom}`"
                    :nom_fichier="`rapport-absences-${item.nom}.pdf`"
                    label="Rapport PDF"
                />

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
                        nom_champ="nom"
                        label="Nom du groupe"
                        placeholder="1AP-A"
                        required
                        v-model:valeur="form.nom"
                        :error="form.errors.nom"
                    />

                    <div class="md:col-span-2">
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
                <CarteSection
                    titre="Étudiants"
                    sous_titre="Du plus absent au moins absent, sur l'année du groupe"
                    :compteur="consultation.etudiants.length"
                    :lien="consultation.liens.etudiants ? { url: consultation.liens.etudiants, label: 'Voir dans la liste' } : null"
                    :avec_marges="false"
                >
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                            <tr class="text-muted-foreground border-b text-xs">
                                <th class="h-10 px-5 text-left font-medium">Étudiant</th>
                                <th class="h-10 px-3 text-right font-medium">Absences</th>
                                <th class="h-10 px-3 text-right font-medium">Non justifiées</th>
                                <th class="h-10 px-3 text-right font-medium">Heures</th>
                                <th class="h-10 px-5 text-right font-medium">Taux</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr
                                v-for="etudiant in consultation.etudiants"
                                :key="etudiant.cne"
                                class="border-b last:border-0"
                                :class="{ 'hover:bg-muted/50 cursor-pointer': etudiant.url }"
                                @click="etudiant.url && router.visit(etudiant.url)"
                            >
                                <td class="h-14 px-5">
                                    <CellulePersonne :nom="etudiant.nom_complet" :sous_texte="etudiant.cne"/>
                                </td>
                                <td class="px-3 text-right tabular-nums">{{ etudiant.nb_absences }}</td>
                                <td class="px-3 text-right tabular-nums"
                                    :class="{ 'text-absent font-medium': etudiant.nb_non_justifiees }">
                                    {{ etudiant.nb_non_justifiees }}
                                </td>
                                <td class="px-3 text-right tabular-nums">{{ formater_nombre(etudiant.heures) }} h</td>
                                <td class="px-5 text-right font-semibold tabular-nums"
                                    :class="`text-${tonalite_taux(etudiant.taux)}`">
                                    {{ formater_nombre(etudiant.taux) }} %
                                </td>
                            </tr>

                            <tr v-if="!consultation.etudiants.length">
                                <td colspan="5" class="text-muted-foreground px-5 py-8 text-center">Aucun étudiant
                                    inscrit dans ce groupe.
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </CarteSection>

                <CarteSection titre="Séances"
                              :lien="{ url: consultation.liens.seances, label: 'Voir toutes les séances' }"
                              :avec_marges="false">
                    <div v-for="bloc in blocs_seances" :key="bloc.titre" class="border-b last:border-0">
                        <p class="text-muted-foreground bg-muted/40 px-5 py-2 text-xs font-medium">{{ bloc.titre }}</p>

                        <Link
                            v-for="seance in bloc.seances"
                            :key="seance.cle"
                            :href="seance.url"
                            class="hover:bg-muted/50 flex flex-wrap items-center gap-x-4 gap-y-1 border-t px-5 py-3 text-sm first-of-type:border-t-0"
                        >
                            <span class="w-36 shrink-0 tabular-nums">
                                <span class="font-medium">{{ seance.date }}</span>
                                <span class="text-muted-foreground block text-xs">{{ seance.horaire }}</span>
                            </span>

                            <span class="flex min-w-0 flex-1 items-center gap-2">
                                <BadgeCouleur :couleur="seance.couleur" :label="seance.module"/>
                                <span class="truncate">{{ seance.intitule }}</span>
                                <span class="text-muted-foreground shrink-0 text-xs">{{ seance.type }}</span>
                            </span>

                            <span class="text-muted-foreground hidden w-40 truncate md:block">{{
                                    seance.enseignant
                                }}</span>

                            <StatutPill :statut="seance.statut"/>
                        </Link>

                        <p v-if="!bloc.seances.length" class="text-muted-foreground px-5 py-4 text-sm">{{
                                bloc.vide
                            }}</p>
                    </div>
                </CarteSection>
            </div>

            <aside class="order-first lg:sticky lg:top-4 lg:order-none">
                <CarteSection titre="En bref">
                    <div class="flex flex-col gap-5">
                        <div class="flex flex-col gap-1 text-sm">
                            <span class="font-medium">{{ consultation.resume.niveau_libelle }}</span>
                            <span class="text-muted-foreground">{{ consultation.resume.cycle }}</span>
                        </div>

                        <Indicateur
                            label="Taux d'absence"
                            :valeur="formater_nombre(consultation.resume.taux)"
                            unite="%"
                            :tonalite="tonalite_taux(consultation.resume.taux)"
                        />

                        <div class="grid grid-cols-2 gap-4">
                            <Indicateur label="Effectif" :valeur="consultation.resume.effectif"/>
                            <Indicateur
                                label="Séances tenues"
                                :valeur="consultation.resume.nb_seances"
                                :detail="`${formater_nombre(consultation.resume.heures)} h`"
                            />
                            <Indicateur label="Absences" :valeur="consultation.resume.nb_absences"/>
                            <Indicateur
                                label="Non justifiées"
                                :valeur="consultation.resume.nb_non_justifiees"
                                :tonalite="consultation.resume.nb_non_justifiees ? 'absent' : 'neutre'"
                            />
                        </div>
                    </div>
                </CarteSection>
            </aside>
        </div>
    </div>
</template>


<script setup lang="ts">
import {computed} from 'vue';
import {Head, Link, router, useForm} from '@inertiajs/vue3';
import {toast} from 'vue-sonner';
import {Button} from '@/components/ui/button';
import CarteSection from '@/_core/detail/carte-section.vue';
import Indicateur from '@/_core/detail/indicateur.vue';
import {formater_nombre, tonalite_taux} from '@/_core/detail/taux';
import {supprimer_avec_confirmation} from '@/_core/dialogs/actions';
import BoutonDocument from '@/_core/documents/bouton-document.vue';
import BadgeCouleur from '@/_core/renders/badge-couleur.vue';
import ChampChaine from '@/_core/renders/champ-chaine.vue';
import ChampSelect from '@/_core/renders/champ-select.vue';
import StatutPill from '@/_core/renders/statut-pill.vue';
import {renders, type ModeVue} from '@/_core/renders';
import type {SelectOption} from '@/_core/renders/types';
import CellulePersonne from '@/_core/table/cellule-personne.vue';

interface Groupe {
    cle: string | null;
    annee_universitaire_id: number | null;
    niveau_etude_id: number | null;
    nom: string | null;
    can_be_deleted: boolean;
}

interface EtudiantGroupe {
    cne: string;
    nom_complet: string;
    nb_absences: number;
    nb_non_justifiees: number;
    heures: number;
    taux: number;
    url: string | null;
}

interface SeanceGroupe {
    cle: string;
    date: string;
    horaire: string;
    type: string;
    module: string;
    intitule: string;
    couleur: string;
    enseignant: string;
    statut: string;
    url: string;
}

interface ConsultationGroupe {
    resume: {
        niveau_code: string;
        niveau_libelle: string;
        couleur: string;
        cycle: string;
        annee: string;
        annee_active: boolean;
        effectif: number;
        nb_seances: number;
        heures: number;
        nb_absences: number;
        nb_non_justifiees: number;
        taux: number;
    };
    etudiants: EtudiantGroupe[];
    seances_a_venir: SeanceGroupe[];
    seances_recentes: SeanceGroupe[];
    liens: {
        etudiants: string | null;
        seances: string;
    };
}

interface GroupeDetailInterface {
    mode_vue: ModeVue;
    titre_page: string;
    item: Groupe;
    annees: SelectOption[];
    niveaux: SelectOption[];
    consultation: ConsultationGroupe | null;
}

const props = defineProps<GroupeDetailInterface>();

const form = useForm({
    annee_universitaire_id: props.item.annee_universitaire_id ?? null,
    niveau_etude_id: props.item.niveau_etude_id ?? null,
    nom: props.item.nom ?? '',
});

const is_editable = computed(() => props.mode_vue === renders.mode_create || props.mode_vue === renders.mode_edit);

const blocs_seances = computed(() => [
    {titre: 'À venir', seances: props.consultation?.seances_a_venir ?? [], vide: 'Aucune séance planifiée à venir.'},
    {titre: 'Récentes', seances: props.consultation?.seances_recentes ?? [], vide: 'Aucune séance passée.'},
]);

const url_list = '/groupes';
const url_detail = props.item.cle ? `/groupe/${props.item.cle}` : '/groupe';
const url_annuler = props.mode_vue === renders.mode_edit ? url_detail : url_list;

const enregistrer = () =>
    form.post(url_detail, {
        preserveState: 'errors',
        onError: () => toast.error('Certains champs sont à corriger.'),
    });

const supprimer = () =>
    supprimer_avec_confirmation(url_detail, `Supprimer le groupe ${props.item.nom} ?`, "Il sera archivé et n'apparaîtra plus dans les listes.");
</script>
