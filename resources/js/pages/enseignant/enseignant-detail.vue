<template>
    <Head :title="titre_page" />

    <div class="flex flex-col gap-6 p-4">
        <!--=====================================================================================================-->
        <!-- En-tête -->
        <!--=====================================================================================================-->
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex flex-col gap-2">
                <h1 class="text-xl font-semibold">{{ titre_page }}</h1>

                <div v-if="mode_vue === renders.mode_consultation" class="flex items-center gap-2 text-sm">
                    <span class="text-muted-foreground">Compte de connexion</span>
                    <StatutPill :statut="item.compte_actif ? 'actif' : 'desactive'" />
                </div>
            </div>

            <div v-if="mode_vue === renders.mode_consultation" class="flex flex-wrap gap-2">
                <Button as-child variant="outline">
                    <Link :href="`${url_detail}/edit`">Modifier</Link>
                </Button>

                <Button v-if="item.can_be_deleted" variant="destructive" @click="supprimer">Supprimer</Button>
            </div>
        </div>

        <p v-if="mode_vue === renders.mode_create" class="bg-champ rounded-md border p-3 text-sm">
            Un compte de connexion sera créé avec cet e-mail. L'enseignant recevra un lien pour choisir son mot de passe.
        </p>

        <!--=====================================================================================================-->
        <!-- Informations -->
        <!--=====================================================================================================-->
        <CarteSection titre="Informations">
            <form class="grid gap-4" @submit.prevent="enregistrer">
                <div class="grid gap-4 md:grid-cols-2">
                    <ChampChaine :mode_vue="mode_vue" nom_champ="nom" label="Nom" required v-model:valeur="form.nom" :error="form.errors.nom" />

                    <ChampChaine :mode_vue="mode_vue" nom_champ="prenom" label="Prénom" required v-model:valeur="form.prenom" :error="form.errors.prenom" />

                    <ChampChaine
                        :mode_vue="mode_vue"
                        nom_champ="email"
                        type="email"
                        label="E-mail"
                        placeholder="prenom.nom@isga.ma"
                        required
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

                    <div v-if="is_editable" class="md:col-span-2">
                        <ChampMultiSelect
                            :mode_vue="mode_vue"
                            nom_champ="modules"
                            label="Modules enseignés"
                            :options="modules"
                            v-model:valeur="form.modules"
                            :error="form.errors.modules"
                        />
                    </div>
                </div>

                <div v-if="is_editable" class="flex gap-2">
                    <Button type="submit" :disabled="form.processing">Enregistrer</Button>
                    <Button as-child variant="outline"><Link :href="url_annuler">Annuler</Link></Button>
                </div>
            </form>
        </CarteSection>

        <!--=====================================================================================================-->
        <!-- Consultation : sections liées à gauche, "En bref" à droite -->
        <!--=====================================================================================================-->
        <div v-if="consultation" class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
            <div class="flex min-w-0 flex-col gap-6">
                <!--=============================================================================================-->
                <!-- Appels à faire -->
                <!--=============================================================================================-->
                <CarteSection
                    titre="Appels à faire"
                    sous_titre="Séances passées dont l'appel n'a pas encore été fait"
                    :compteur="consultation.resume.nb_appels_a_faire"
                    :lien="consultation.resume.nb_appels_a_faire ? { url: consultation.liens.appels_a_faire, label: 'Voir tous' } : null"
                    :avec_marges="false"
                >
                    <LigneSeance v-for="seance in consultation.appels_a_faire" :key="seance.cle" :seance="seance" />

                    <p v-if="!consultation.appels_a_faire.length" class="text-muted-foreground px-5 py-8 text-center text-sm">
                        Tous les appels sont à jour.
                    </p>
                </CarteSection>

                <!--=============================================================================================-->
                <!-- Prochaines séances -->
                <!--=============================================================================================-->
                <CarteSection
                    titre="Prochaines séances"
                    :lien="{ url: consultation.liens.seances, label: 'Voir toutes ses séances' }"
                    :avec_marges="false"
                >
                    <LigneSeance v-for="seance in consultation.a_venir" :key="seance.cle" :seance="seance" />

                    <p v-if="!consultation.a_venir.length" class="text-muted-foreground px-5 py-8 text-center text-sm">
                        Aucune séance planifiée à venir.
                    </p>
                </CarteSection>

                <!--=============================================================================================-->
                <!-- Modules enseignés -->
                <!--=============================================================================================-->
                <CarteSection titre="Modules enseignés" :compteur="consultation.modules.length" :avec_marges="false">
                    <Link
                        v-for="module in consultation.modules"
                        :key="module.url"
                        :href="module.url"
                        class="hover:bg-muted/50 flex items-center gap-3 border-b px-5 py-3 text-sm last:border-0"
                    >
                        <BadgeCouleur :couleur="module.couleur" :label="module.code" />
                        <span class="min-w-0 flex-1 truncate">{{ module.intitule }}</span>
                        <span class="text-muted-foreground shrink-0 text-xs">{{ module.niveau }}</span>
                    </Link>

                    <p v-if="!consultation.modules.length" class="text-muted-foreground px-5 py-8 text-center text-sm">
                        Aucun module attribué. Utilisez « Modifier » pour lui attribuer des modules.
                    </p>
                </CarteSection>

                <!--=============================================================================================-->
                <!-- Groupes de l'année -->
                <!--=============================================================================================-->
                <CarteSection titre="Groupes cette année" :compteur="consultation.groupes.length">
                    <div v-if="consultation.groupes.length" class="flex flex-wrap gap-2">
                        <Link v-for="groupe in consultation.groupes" :key="groupe.url" :href="groupe.url" :title="groupe.niveau">
                            <BadgeCouleur :couleur="groupe.couleur" :label="groupe.nom" />
                        </Link>
                    </div>

                    <p v-else class="text-muted-foreground text-center text-sm">Aucune séance cette année.</p>
                </CarteSection>
            </div>

            <!--=================================================================================================-->
            <!-- En bref : reste visible au défilement -->
            <!--=================================================================================================-->
            <aside class="order-first lg:sticky lg:top-4 lg:order-none">
                <CarteSection titre="En bref" sous_titre="Année en cours">
                    <div class="flex flex-col gap-5">
                        <Indicateur
                            label="Appels à faire"
                            :valeur="consultation.resume.nb_appels_a_faire"
                            :tonalite="consultation.resume.nb_appels_a_faire ? 'attente' : 'present'"
                        />

                        <div class="grid grid-cols-2 gap-4">
                            <Indicateur
                                label="Séances tenues"
                                :valeur="consultation.resume.nb_seances"
                                :detail="`${formater_nombre(consultation.resume.heures_enseignees)} h`"
                            />
                            <Indicateur
                                label="Taux d'absence"
                                :valeur="formater_nombre(consultation.resume.taux)"
                                unite="%"
                                detail="dans ses séances"
                                :tonalite="tonalite_taux(consultation.resume.taux)"
                            />
                            <Indicateur label="Absences relevées" :valeur="consultation.resume.nb_absences" />
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
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import CarteSection from '@/_core/detail/carte-section.vue';
import Indicateur from '@/_core/detail/indicateur.vue';
import LigneSeance from '@/_core/detail/ligne-seance.vue';
import { formater_nombre, tonalite_taux } from '@/_core/detail/taux';
import type { SeanceResume } from '@/_core/detail/types';
import { supprimer_avec_confirmation } from '@/_core/dialogs/actions';
import BadgeCouleur from '@/_core/renders/badge-couleur.vue';
import ChampChaine from '@/_core/renders/champ-chaine.vue';
import ChampMultiSelect from '@/_core/renders/champ-multi-select.vue';
import StatutPill from '@/_core/renders/statut-pill.vue';
import { renders, type ModeVue } from '@/_core/renders';
import type { SelectOption } from '@/_core/renders/types';

interface Enseignant {
    cle: string | null;
    nom: string | null;
    prenom: string | null;
    email: string | null;
    telephone: string | null;
    modules: number[];
    compte_actif: boolean;
    can_be_deleted: boolean;
}

interface ModuleEnseigne {
    code: string;
    intitule: string;
    niveau: string;
    couleur: string;
    url: string;
}

interface GroupeEnseigne {
    nom: string;
    niveau: string;
    couleur: string;
    url: string;
}

interface ConsultationEnseignant {
    resume: {
        nb_seances: number;
        heures_enseignees: number;
        nb_appels_a_faire: number;
        nb_absences: number;
        nb_non_justifiees: number;
        taux: number;
    };
    appels_a_faire: SeanceResume[];
    a_venir: SeanceResume[];
    modules: ModuleEnseigne[];
    groupes: GroupeEnseigne[];
    liens: {
        seances: string;
        appels_a_faire: string;
    };
}

interface EnseignantDetailInterface {
    mode_vue: ModeVue;
    titre_page: string;
    item: Enseignant;
    modules: SelectOption[];
    consultation: ConsultationEnseignant | null;
}

const props = defineProps<EnseignantDetailInterface>();

const form = useForm({
    nom: props.item.nom ?? '',
    prenom: props.item.prenom ?? '',
    email: props.item.email ?? '',
    telephone: props.item.telephone ?? '',
    modules: props.item.modules ?? [],
});

const is_editable = computed(() => props.mode_vue === renders.mode_create || props.mode_vue === renders.mode_edit);

const url_list = '/enseignants';
const url_detail = props.item.cle ? `/enseignant/${props.item.cle}` : '/enseignant';
const url_annuler = props.mode_vue === renders.mode_edit ? url_detail : url_list;

const enregistrer = () =>
    form.post(url_detail, {
        preserveState: 'errors',
        onError: () => toast.error('Certains champs sont à corriger.'),
    });

const supprimer = () =>
    supprimer_avec_confirmation(
        url_detail,
        `Supprimer l'enseignant ${props.item.prenom} ${props.item.nom} ?`,
        'Sa fiche sera archivée et son compte de connexion désactivé.',
    );
</script>
