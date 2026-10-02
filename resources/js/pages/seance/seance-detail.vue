<template>
    <Head :title="titre_page" />

    <div class="flex flex-col gap-6 p-4">
        <!--=====================================================================================================-->
        <!-- En-tête -->
        <!--=====================================================================================================-->
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex flex-col gap-2">
                <h1 class="text-xl font-semibold">{{ titre_page }}</h1>

                <div v-if="consultation" class="flex flex-wrap items-center gap-2 text-sm">
                    <BadgeCouleur :couleur="consultation.contexte.module.couleur" :label="consultation.contexte.module.code" />
                    <span class="text-muted-foreground tabular-nums">{{ item.heure_debut }} – {{ item.heure_fin }}, {{ item.type }}</span>
                    <StatutPill :statut="consultation.resume.statut" />
                </div>

                <p v-if="mode_vue === renders.mode_create && annee_active" class="text-muted-foreground text-sm">
                    Séance de l'année universitaire en cours ({{ annee_active.libelle }}).
                </p>
            </div>

            <div v-if="mode_vue === renders.mode_consultation" class="flex flex-wrap justify-end gap-2">
                <Button v-if="!item.annulee" as-child>
                    <Link :href="`${url_detail}/appel`">{{ consultation?.resume.appel_fait ? "Voir l'appel" : "Faire l'appel" }}</Link>
                </Button>

                <BoutonDocument
                    :url="`${url_detail}/feuille-presence`"
                    :titre="titre_feuille"
                    :nom_fichier="`feuille-presence-${item.date}.pdf`"
                    label="Feuille de présence"
                />

                <Button as-child variant="outline">
                    <Link :href="`${url_detail}/edit`">Modifier</Link>
                </Button>

                <Button v-if="item.can_be_deleted" variant="destructive" @click="supprimer">Supprimer</Button>
            </div>
        </div>

        <!--=====================================================================================================-->
        <!-- Informations : Groupe → Module → Enseignant ; dates limitées à l'année -->
        <!--=====================================================================================================-->
        <CarteSection titre="Informations">
            <form class="grid gap-4" @submit.prevent="enregistrer">
                <div class="grid gap-4 md:grid-cols-2">
                    <ChampSelect
                        :mode_vue="mode_vue"
                        nom_champ="groupe_id"
                        label="Groupe"
                        placeholder="Choisir un groupe"
                        required
                        :options="groupes_proposes"
                        v-model:valeur="form.groupe_id"
                        :error="form.errors.groupe_id"
                    />

                    <ChampSelect :mode_vue="mode_vue" nom_champ="type" label="Type" required :options="types" v-model:valeur="form.type" :error="form.errors.type" />

                    <ChampSelect
                        :mode_vue="mode_vue"
                        nom_champ="module_id"
                        label="Module"
                        :placeholder="form.groupe_id ? 'Choisir un module' : 'Choisir d\'abord un groupe'"
                        required
                        :options="modules_du_groupe"
                        v-model:valeur="form.module_id"
                        :error="form.errors.module_id"
                    />

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

                    <ChampDate
                        :mode_vue="mode_vue"
                        nom_champ="date"
                        label="Date"
                        required
                        :date_min="limites_date.min"
                        :date_max="limites_date.max"
                        v-model:valeur="form.date"
                        :error="form.errors.date"
                    />

                    <div class="grid grid-cols-2 gap-4">
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

                    <ChampChaine :mode_vue="mode_vue" nom_champ="salle" label="Salle" placeholder="B204" v-model:valeur="form.salle" :error="form.errors.salle" />

                    <div v-if="mode_vue !== renders.mode_create" class="md:pt-6">
                        <ChampBoolean
                            :mode_vue="mode_vue"
                            nom_champ="annulee"
                            label="Séance annulée"
                            v-model:valeur="form.annulee"
                            :error="form.errors.annulee"
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
        <!-- Consultation : l'appel et le contexte à gauche, "En bref" à droite -->
        <!--=====================================================================================================-->
        <div v-if="consultation" class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
            <div class="flex min-w-0 flex-col gap-6">
                <!--=============================================================================================-->
                <!-- Appel : les absents -->
                <!--=============================================================================================-->
                <CarteSection
                    titre="Appel"
                    :sous_titre="sous_titre_appel"
                    :compteur="consultation.resume.appel_fait ? consultation.resume.nb_absents : null"
                    :lien="consultation.resume.appel_fait && !item.annulee ? { url: consultation.liens.appel, label: 'Ouvrir l\'appel' } : null"
                    :avec_marges="false"
                >
                    <p v-if="item.annulee" class="text-muted-foreground px-5 py-8 text-center text-sm">Séance annulée : pas d'appel.</p>

                    <div v-else-if="!consultation.resume.appel_fait" class="flex flex-col items-center gap-3 px-5 py-8 text-center">
                        <p class="text-muted-foreground text-sm">
                            {{ consultation.resume.statut === 'planifiee' ? "L'appel pourra être fait le jour de la séance." : "L'appel de cette séance n'a pas encore été fait." }}
                        </p>

                        <Button v-if="consultation.resume.statut === 'appel_a_faire'" as-child>
                            <Link :href="consultation.liens.appel">Faire l'appel</Link>
                        </Button>
                    </div>

                    <template v-else>
                        <Link
                            v-for="absent in consultation.absents"
                            :key="absent.cne"
                            :href="absent.url"
                            class="hover:bg-muted/50 flex flex-wrap items-center gap-x-4 gap-y-2 border-b px-5 py-3 text-sm last:border-0"
                        >
                            <span class="min-w-0 flex-1">
                                <CellulePersonne :nom="absent.nom_complet" :sous_texte="absent.cne" />
                            </span>

                            <span v-if="absent.remarque" class="text-muted-foreground max-w-xs truncate text-xs">{{ absent.remarque }}</span>

                            <StatutPill :statut="absent.justifiee ? 'justifie' : 'absent'" :label="absent.justifiee ? 'Justifiée' : 'Non justifiée'" />
                        </Link>

                        <p v-if="!consultation.absents.length" class="text-present px-5 py-8 text-center text-sm font-medium">Tous les étudiants étaient présents.</p>
                    </template>
                </CarteSection>

                <!--=============================================================================================-->
                <!-- Contexte : module, groupe, enseignant -->
                <!--=============================================================================================-->
                <CarteSection titre="Contexte" :avec_marges="false">
                    <Link :href="consultation.contexte.module.url" class="hover:bg-muted/50 flex items-center gap-3 border-b px-5 py-3 text-sm">
                        <span class="text-muted-foreground w-24 shrink-0 text-xs">Module</span>
                        <BadgeCouleur :couleur="consultation.contexte.module.couleur" :label="consultation.contexte.module.code" />
                        <span class="min-w-0 truncate">{{ consultation.contexte.module.intitule }}</span>
                    </Link>

                    <Link :href="consultation.contexte.groupe.url" class="hover:bg-muted/50 flex items-center gap-3 border-b px-5 py-3 text-sm">
                        <span class="text-muted-foreground w-24 shrink-0 text-xs">Groupe</span>
                        <BadgeCouleur :couleur="consultation.contexte.groupe.couleur" :label="consultation.contexte.groupe.nom" />
                        <span class="text-muted-foreground min-w-0 truncate">{{ consultation.contexte.groupe.niveau }}</span>
                    </Link>

                    <Link :href="consultation.contexte.enseignant.url" class="hover:bg-muted/50 flex items-center gap-3 px-5 py-3 text-sm">
                        <span class="text-muted-foreground w-24 shrink-0 text-xs">Enseignant</span>
                        <CellulePersonne :nom="consultation.contexte.enseignant.nom_complet" :sous_texte="consultation.contexte.enseignant.email" />
                    </Link>
                </CarteSection>
            </div>

            <!--=================================================================================================-->
            <!-- En bref : reste visible au défilement -->
            <!--=================================================================================================-->
            <aside class="order-first lg:sticky lg:top-4 lg:order-none">
                <CarteSection titre="En bref" :sous_titre="`${formater_nombre(consultation.resume.duree)} h de ${item.type?.toLowerCase()}`">
                    <div class="flex flex-col gap-5">
                        <StatutPill :statut="consultation.resume.statut" class="self-start" />

                        <template v-if="consultation.resume.appel_fait">
                            <Indicateur
                                label="Taux de présence"
                                :valeur="formater_nombre(consultation.resume.taux_presence ?? 0)"
                                unite="%"
                                :tonalite="tonalite_presence(consultation.resume.taux_presence ?? 0)"
                            />

                            <div class="grid grid-cols-2 gap-4">
                                <Indicateur label="Présents" :valeur="consultation.resume.nb_presents ?? 0" :detail="`sur ${consultation.resume.effectif}`" tonalite="present" />
                                <Indicateur label="Absents" :valeur="consultation.resume.nb_absents" :tonalite="consultation.resume.nb_absents ? 'absent' : 'neutre'" />
                                <Indicateur label="Justifiées" :valeur="consultation.resume.nb_justifiees" tonalite="justifie" />
                                <Indicateur label="Effectif" :valeur="consultation.resume.effectif" />
                            </div>
                        </template>

                        <template v-else>
                            <Indicateur label="Effectif du groupe" :valeur="consultation.resume.effectif" />
                            <p class="text-muted-foreground text-sm">Les présences seront connues une fois l'appel fait.</p>
                        </template>
                    </div>
                </CarteSection>
            </aside>
        </div>
    </div>
</template>


<script setup lang="ts">
import { computed, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import CarteSection from '@/_core/detail/carte-section.vue';
import Indicateur from '@/_core/detail/indicateur.vue';
import { formater_nombre } from '@/_core/detail/taux';
import { supprimer_avec_confirmation } from '@/_core/dialogs/actions';
import BoutonDocument from '@/_core/documents/bouton-document.vue';
import BadgeCouleur from '@/_core/renders/badge-couleur.vue';
import ChampBoolean from '@/_core/renders/champ-boolean.vue';
import ChampChaine from '@/_core/renders/champ-chaine.vue';
import ChampDate from '@/_core/renders/champ-date.vue';
import ChampHeure from '@/_core/renders/champ-heure.vue';
import ChampSelect from '@/_core/renders/champ-select.vue';
import StatutPill from '@/_core/renders/statut-pill.vue';
import type { Tonalite } from '@/_core/renders/statuts';
import { renders, type ModeVue } from '@/_core/renders';
import type { SelectOption } from '@/_core/renders/types';
import CellulePersonne from '@/_core/table/cellule-personne.vue';

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

interface AnneeActive {
    id: number;
    libelle: string;
    date_debut: string | null;
    date_fin: string | null;
}

interface GroupeOption extends SelectOption {
    annee_universitaire_id: number;
    niveau_etude_id: number;
    date_debut: string | null;
    date_fin: string | null;
}

interface ModuleOption extends SelectOption {
    niveau_etude_id: number;
    enseignant_ids: number[];
}

interface AbsentSeance {
    nom_complet: string;
    cne: string;
    justifiee: boolean;
    remarque: string | null;
    url: string;
}

interface ConsultationSeance {
    resume: {
        statut: string;
        appel_fait: boolean;
        appel_fait_le: string | null;
        appel_fait_par: string | null;
        effectif: number;
        nb_presents: number | null;
        nb_absents: number;
        nb_justifiees: number;
        taux_presence: number | null;
        duree: number;
    };
    absents: AbsentSeance[];
    contexte: {
        module: { code: string; intitule: string; couleur: string; url: string };
        groupe: { nom: string; niveau: string; couleur: string; url: string };
        enseignant: { nom_complet: string; email: string; url: string };
    };
    liens: {
        appel: string;
    };
}

interface SeanceDetailInterface {
    mode_vue: ModeVue;
    titre_page: string;
    item: Seance;
    annee_active: AnneeActive | null;
    modules: ModuleOption[];
    enseignants: SelectOption[];
    groupes: GroupeOption[];
    types: SelectOption[];
    consultation: ConsultationSeance | null;
}

const props = defineProps<SeanceDetailInterface>();

const est_creation = props.mode_vue === renders.mode_create;

//==============================================================================================================
// Limites de la date :
// - création : l'année universitaire en cours
// - modification : l'année du groupe de la séance (on corrige une séance dans sa propre année)
//==============================================================================================================
const limites_initiales = () => {
    if (est_creation) {
        return { min: props.annee_active?.date_debut ?? null, max: props.annee_active?.date_fin ?? null };
    }

    const groupe = props.groupes.find((option) => option.valeur === props.item.groupe_id);

    return { min: groupe?.date_debut ?? null, max: groupe?.date_fin ?? null };
};

const date_permise = (date: string | null, min: string | null, max: string | null) => !date || ((!min || date >= min) && (!max || date <= max));

//==============================================================================================================
// Formulaire : en création, la date du jour n'est gardée que si elle est dans l'année en cours
//==============================================================================================================
const date_initiale = (() => {
    const { min, max } = limites_initiales();

    return date_permise(props.item.date, min, max) ? props.item.date ?? '' : '';
})();

const form = useForm({
    module_id: props.item.module_id ?? null,
    enseignant_id: props.item.enseignant_id ?? null,
    groupe_id: props.item.groupe_id ?? null,
    date: date_initiale,
    heure_debut: props.item.heure_debut ?? '',
    heure_fin: props.item.heure_fin ?? '',
    type: props.item.type ?? null,
    salle: props.item.salle ?? '',
    annulee: props.item.annulee ?? false,
});

const is_editable = computed(() => props.mode_vue === renders.mode_create || props.mode_vue === renders.mode_edit);

//==============================================================================================================
// Groupes proposés : en création, ceux de l'année en cours ; sinon tous
//==============================================================================================================
const groupes_proposes = computed<GroupeOption[]>(() => {
    if (!est_creation || !props.annee_active) return props.groupes;

    return props.groupes.filter((groupe) => groupe.annee_universitaire_id === props.annee_active?.id);
});

const groupe_choisi = computed(() => props.groupes.find((option) => option.valeur === form.groupe_id) ?? null);

const limites_date = computed(() => {
    if (est_creation) return limites_initiales();

    return { min: groupe_choisi.value?.date_debut ?? null, max: groupe_choisi.value?.date_fin ?? null };
});

//==============================================================================================================
// Groupe → modules de son niveau d'études (RG-15) ; en consultation : tous, pour afficher le libellé
//==============================================================================================================
const modules_du_groupe = computed<ModuleOption[]>(() => {
    if (!is_editable.value) return props.modules;
    if (!groupe_choisi.value) return [];

    return props.modules.filter((module) => module.niveau_etude_id === groupe_choisi.value?.niveau_etude_id);
});

//==============================================================================================================
// Module → ses enseignants ; en consultation : tous
//==============================================================================================================
const enseignants_du_module = computed(() => {
    if (!is_editable.value) return props.enseignants;

    const module = props.modules.find((option) => option.valeur === form.module_id);

    if (!module) return [];

    return props.enseignants.filter((option) => module.enseignant_ids.includes(Number(option.valeur)));
});

//==============================================================================================================
// Changement de groupe : module hors niveau retiré ; en modification, date hors de l'année du groupe retirée
//==============================================================================================================
watch(
    () => form.groupe_id,
    () => {
        if (!is_editable.value) return;

        if (!modules_du_groupe.value.some((module) => module.valeur === form.module_id)) {
            form.module_id = null;
        }

        if (!date_permise(form.date, limites_date.value.min, limites_date.value.max)) {
            form.date = '';
        }
    },
);

//==============================================================================================================
// Changement de module : on vide l'enseignant s'il n'enseigne pas le nouveau module
//==============================================================================================================
watch(
    () => form.module_id,
    () => {
        if (!is_editable.value) return;

        if (!enseignants_du_module.value.some((option) => option.valeur === form.enseignant_id)) {
            form.enseignant_id = null;
        }
    },
);

//==============================================================================================================
// Appel : qui l'a fait et quand ; taux de présence coloré (vert ≥ 95 %, orange ≥ 85 %, rouge en dessous)
//==============================================================================================================
const sous_titre_appel = computed(() => {
    const resume = props.consultation?.resume;

    if (!resume?.appel_fait) return null;

    return `Fait le ${resume.appel_fait_le}${resume.appel_fait_par ? ` par ${resume.appel_fait_par}` : ''}`;
});

const tonalite_presence = (taux: number): Tonalite => {
    if (taux >= 95) return 'present';
    if (taux >= 85) return 'attente';

    return 'absent';
};

const url_list = '/seances';
const url_detail = props.item.cle ? `/seance/${props.item.cle}` : '/seance';
const url_annuler = props.mode_vue === renders.mode_edit ? url_detail : url_list;

const titre_feuille = computed(() => {
    const groupe = props.groupes.find((option) => option.valeur === props.item.groupe_id)?.label ?? '';
    const date = props.item.date?.split('-').reverse().join('/') ?? '';

    return `Feuille de présence ${groupe} du ${date}`;
});

const enregistrer = () =>
    form.post(url_detail, {
        preserveState: 'errors',
        onError: () => toast.error('Certains champs sont à corriger.'),
    });

const supprimer = () =>
    supprimer_avec_confirmation(
        url_detail,
        'Supprimer cette séance ?',
        'Elle disparaîtra du planning. Pour garder une trace, vous pouvez plutôt la marquer comme annulée.',
    );
</script>
