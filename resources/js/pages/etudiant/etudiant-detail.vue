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
                    <span class="text-muted-foreground tabular-nums">{{ item.cne }}</span>

                    <Link v-if="consultation.resume.groupe" :href="consultation.resume.groupe.url">
                        <BadgeCouleur :couleur="consultation.resume.groupe.couleur"
                                      :label="consultation.resume.groupe.nom"/>
                    </Link>
                    <StatutPill v-else statut="desactive" label="Non inscrit cette année"/>
                </div>
            </div>

            <div v-if="mode_vue === renders.mode_consultation" class="flex flex-wrap gap-2">
                <BoutonDocument
                    :url="`${url_detail}/releve`"
                    :titre="`Relevé d'absences de ${item.prenom} ${item.nom}`"
                    :nom_fichier="`releve-absences-${item.cne}.pdf`"
                    label="Relevé PDF"
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
                    <ChampChaine
                        :mode_vue="mode_vue"
                        nom_champ="cne"
                        label="CNE"
                        placeholder="R130245678"
                        required
                        v-model:valeur="form.cne"
                        :error="form.errors.cne"
                    />

                    <ChampSelect
                        v-if="annee_active"
                        :mode_vue="mode_vue"
                        nom_champ="groupe_id"
                        :label="`Groupe en ${annee_active}`"
                        :options="groupes"
                        v-model:valeur="form.groupe_id"
                        :error="form.errors.groupe_id"
                    />

                    <p v-else class="text-muted-foreground text-sm md:pt-7">
                        Aucune année universitaire active : l'inscription se fera une fois une année activée.
                    </p>

                    <ChampChaine :mode_vue="mode_vue" nom_champ="nom" label="Nom" required v-model:valeur="form.nom"
                                 :error="form.errors.nom"/>

                    <ChampChaine :mode_vue="mode_vue" nom_champ="prenom" label="Prénom" required
                                 v-model:valeur="form.prenom" :error="form.errors.prenom"/>

                    <ChampChaine
                        :mode_vue="mode_vue"
                        nom_champ="email"
                        type="email"
                        label="E-mail"
                        placeholder="prenom.nom@exemple.ma"
                        v-model:valeur="form.email"
                        :error="form.errors.email"
                    />

                    <ChampChaine
                        :mode_vue="mode_vue"
                        nom_champ="telephone"
                        type="tel"
                        label="Téléphone"
                        placeholder="0612345678"
                        v-model:valeur="form.telephone"
                        :error="form.errors.telephone"
                    />

                    <ChampDate
                        :mode_vue="mode_vue"
                        nom_champ="date_naissance"
                        label="Date de naissance"
                        v-model:valeur="form.date_naissance"
                        :error="form.errors.date_naissance"
                    />
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
                <CarteSection titre="Absences récentes" :sous_titre="consultation.resume.periode" :avec_marges="false">
                    <div
                        v-for="(absence, index) in consultation.absences"
                        :key="index"
                        class="flex flex-wrap items-center gap-x-4 gap-y-1 border-b px-5 py-3 text-sm last:border-0"
                    >
                        <span class="w-36 shrink-0 tabular-nums">
                            <span class="font-medium">{{ absence.date }}</span>
                            <span class="text-muted-foreground block text-xs">{{ absence.horaire }}</span>
                        </span>

                        <span class="min-w-0 flex-1 truncate">
                            {{ absence.module }}
                            <span class="text-muted-foreground text-xs">{{ absence.type }}</span>
                        </span>

                        <StatutPill :statut="absence.justifiee ? 'justifie' : 'absent'"
                                    :label="absence.justifiee ? 'Justifiée' : 'Non justifiée'"/>
                    </div>

                    <p v-if="!consultation.absences.length" class="text-muted-foreground px-5 py-8 text-center text-sm">
                        Aucune absence cette année.</p>
                </CarteSection>

                <CarteSection
                    titre="Justificatifs"
                    sous_titre="Les 5 derniers déposés"
                    :lien="{ url: consultation.liens.justificatifs, label: 'Voir tous ses justificatifs' }"
                    :avec_marges="false"
                >
                    <Link
                        v-for="justificatif in consultation.justificatifs"
                        :key="justificatif.url"
                        :href="justificatif.url"
                        class="hover:bg-muted/50 flex flex-wrap items-center gap-x-4 gap-y-1 border-b px-5 py-3 text-sm last:border-0"
                    >
                        <span class="w-36 shrink-0">
                            <span class="font-medium">{{ justificatif.type }}</span>
                            <span class="text-muted-foreground block text-xs tabular-nums">Déposé le {{
                                    justificatif.date_depot
                                }}</span>
                        </span>

                        <span class="text-muted-foreground min-w-0 flex-1 truncate tabular-nums">{{
                                justificatif.periode
                            }}</span>

                        <span class="flex items-center gap-2">
                            <StatutPill v-if="justificatif.hors_delai" statut="hors_delai"/>
                            <StatutPill :statut="justificatif.statut"/>
                        </span>
                    </Link>

                    <p v-if="!consultation.justificatifs.length"
                       class="text-muted-foreground px-5 py-8 text-center text-sm">Aucun justificatif déposé.</p>
                </CarteSection>

                <CarteSection titre="Parcours de l'étudiant" :avec_marges="false">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                            <tr class="text-muted-foreground border-b text-xs">
                                <th class="h-10 px-5 text-left font-medium">Année</th>
                                <th class="h-10 px-3 text-left font-medium">Groupe</th>
                                <th class="h-10 px-3 text-left font-medium">Niveau d'études</th>
                                <th class="h-10 px-5 text-left font-medium">Décision</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="ligne in historique" :key="ligne.annee" class="border-b last:border-0">
                                <td class="h-12 px-5">
                                    {{ ligne.annee }}
                                    <StatutPill v-if="ligne.active" statut="en_cours" class="ml-2"/>
                                </td>
                                <td class="px-3">{{ ligne.groupe }}</td>
                                <td class="px-3">{{ ligne.niveau }}</td>
                                <td class="px-5">
                                    <StatutPill v-if="ligne.decision" :statut="ligne.decision"/>
                                    <span v-else class="text-muted-foreground">—</span>
                                </td>
                            </tr>

                            <tr v-if="!historique.length">
                                <td colspan="4" class="text-muted-foreground px-5 py-8 text-center">Aucune inscription
                                    pour le moment.
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </CarteSection>
            </div>

            <aside class="order-first lg:sticky lg:top-4 lg:order-none">
                <CarteSection titre="En bref" :sous_titre="consultation.resume.periode">
                    <div class="flex flex-col gap-5">
                        <div v-if="consultation.resume.groupe" class="flex flex-col gap-1 text-sm">
                            <span class="font-medium">{{ consultation.resume.groupe.nom }}</span>
                            <span class="text-muted-foreground">{{ consultation.resume.groupe.niveau }}</span>
                        </div>

                        <Indicateur
                            label="Taux d'absence"
                            :valeur="formater_nombre(consultation.resume.taux)"
                            unite="%"
                            :detail="`${formater_nombre(consultation.resume.heures)} h d'absence`"
                            :tonalite="tonalite_taux(consultation.resume.taux)"
                        />

                        <div class="grid grid-cols-2 gap-4">
                            <Indicateur label="Absences" :valeur="consultation.resume.nb_absences"/>
                            <Indicateur
                                label="Non justifiées"
                                :valeur="consultation.resume.nb_non_justifiees"
                                :tonalite="consultation.resume.nb_non_justifiees ? 'absent' : 'neutre'"
                            />
                            <Indicateur label="Justifiées" :valeur="consultation.resume.nb_justifiees"
                                        tonalite="justifie"/>
                            <Indicateur
                                label="Justificatifs en attente"
                                :valeur="consultation.resume.justificatifs_en_attente"
                                :tonalite="consultation.resume.justificatifs_en_attente ? 'attente' : 'neutre'"
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
import {Head, Link, useForm} from '@inertiajs/vue3';
import {toast} from 'vue-sonner';
import {Button} from '@/components/ui/button';
import CarteSection from '@/_core/detail/carte-section.vue';
import Indicateur from '@/_core/detail/indicateur.vue';
import {formater_nombre, tonalite_taux} from '@/_core/detail/taux';
import {supprimer_avec_confirmation} from '@/_core/dialogs/actions';
import BoutonDocument from '@/_core/documents/bouton-document.vue';
import BadgeCouleur from '@/_core/renders/badge-couleur.vue';
import ChampChaine from '@/_core/renders/champ-chaine.vue';
import ChampDate from '@/_core/renders/champ-date.vue';
import ChampSelect from '@/_core/renders/champ-select.vue';
import StatutPill from '@/_core/renders/statut-pill.vue';
import {renders, type ModeVue} from '@/_core/renders';
import type {SelectOption} from '@/_core/renders/types';

interface Etudiant {
    cle: string | null;
    groupe_id: number | null;
    cne: string | null;
    nom: string | null;
    prenom: string | null;
    email: string | null;
    telephone: string | null;
    date_naissance: string | null;
    can_be_deleted: boolean;
}

interface LigneHistorique {
    annee: string;
    active: boolean;
    groupe: string;
    niveau: string;
    decision: string | null;
}

interface AbsenceEtudiant {
    date: string;
    horaire: string;
    module: string;
    type: string;
    justifiee: boolean;
    remarque: string | null;
}

interface JustificatifEtudiant {
    type: string;
    periode: string;
    date_depot: string;
    statut: string;
    hors_delai: boolean;
    url: string;
}

interface ConsultationEtudiant {
    resume: {
        groupe: { nom: string; niveau: string; couleur: string; url: string } | null;
        periode: string;
        nb_absences: number;
        nb_justifiees: number;
        nb_non_justifiees: number;
        heures: number;
        taux: number;
        justificatifs_en_attente: number;
    };
    absences: AbsenceEtudiant[];
    justificatifs: JustificatifEtudiant[];
    liens: {
        justificatifs: string;
    };
}

interface EtudiantDetailInterface {
    mode_vue: ModeVue;
    titre_page: string;
    item: Etudiant;
    groupes: SelectOption[];
    annee_active: string | null;
    historique: LigneHistorique[];
    consultation: ConsultationEtudiant | null;
}

const props = defineProps<EtudiantDetailInterface>();

const form = useForm({
    groupe_id: props.item.groupe_id ?? '',
    cne: props.item.cne ?? '',
    nom: props.item.nom ?? '',
    prenom: props.item.prenom ?? '',
    email: props.item.email ?? '',
    telephone: props.item.telephone ?? '',
    date_naissance: props.item.date_naissance ?? '',
});

const is_editable = computed(() => props.mode_vue === renders.mode_create || props.mode_vue === renders.mode_edit);

const url_list = '/etudiants';
const url_detail = props.item.cle ? `/etudiant/${props.item.cle}` : '/etudiant';
const url_annuler = props.mode_vue === renders.mode_edit ? url_detail : url_list;

const enregistrer = () =>
    form.post(url_detail, {
        preserveState: 'errors',
        onError: () => toast.error('Certains champs sont à corriger.'),
    });

const supprimer = () =>
    supprimer_avec_confirmation(
        url_detail,
        `Supprimer l'étudiant ${props.item.prenom} ${props.item.nom} ?`,
        "Sa fiche sera archivée et n'apparaîtra plus dans les listes.",
    );
</script>
