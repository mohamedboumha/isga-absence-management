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
                    <Link :href="consultation.liens.niveau">
                        <BadgeCouleur :couleur="consultation.resume.couleur" :label="consultation.resume.niveau_code"/>
                    </Link>
                    <span class="text-muted-foreground">Semestre {{ item.semestre }}, {{ item.volume_horaire }} h</span>
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

                    <ChampChaine :mode_vue="mode_vue" nom_champ="code" label="Code" placeholder="3CI-IABD-01" required
                                 v-model:valeur="form.code" :error="form.errors.code"/>

                    <ChampChaine
                        :mode_vue="mode_vue"
                        nom_champ="intitule"
                        label="Intitulé"
                        placeholder="Bases de données"
                        required
                        v-model:valeur="form.intitule"
                        :error="form.errors.intitule"
                    />

                    <ChampSelect
                        :mode_vue="mode_vue"
                        nom_champ="semestre"
                        label="Semestre"
                        :placeholder="form.niveau_etude_id ? 'Choisir un semestre' : 'Choisir d\'abord un niveau'"
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
                <!-- Avancement par groupe -->
                <!--=============================================================================================-->
                <CarteSection
                    titre="Avancement par groupe"
                    :sous_titre="`Heures faites cette année sur ${consultation.resume.volume} h prévues`"
                    :compteur="consultation.avancement.length"
                    :avec_marges="false"
                >
                    <Link
                        v-for="groupe in consultation.avancement"
                        :key="groupe.url"
                        :href="groupe.url"
                        class="hover:bg-muted/50 flex items-center gap-4 border-b px-5 py-3 text-sm last:border-0"
                    >
                        <span class="w-28 shrink-0 font-medium">{{ groupe.nom }}</span>
                        <BarreProgression :pourcentage="groupe.pourcentage" :label="`Avancement de ${groupe.nom}`"
                                          class="flex-1"/>
                        <span class="text-muted-foreground w-28 shrink-0 text-right tabular-nums">
                            {{ formater_nombre(groupe.heures) }} / {{ consultation.resume.volume }} h
                        </span>
                    </Link>

                    <p v-if="!consultation.avancement.length"
                       class="text-muted-foreground px-5 py-8 text-center text-sm">
                        Aucun groupe de ce niveau cette année.
                    </p>
                </CarteSection>

                <!--=============================================================================================-->
                <!-- Enseignants -->
                <!--=============================================================================================-->
                <CarteSection titre="Enseignants" :compteur="consultation.enseignants.length" :avec_marges="false">
                    <Link
                        v-for="enseignant in consultation.enseignants"
                        :key="enseignant.url"
                        :href="enseignant.url"
                        class="hover:bg-muted/50 flex items-center border-b px-5 py-3 text-sm last:border-0"
                    >
                        <CellulePersonne :nom="enseignant.nom_complet" :sous_texte="enseignant.email"/>
                    </Link>

                    <p v-if="!consultation.enseignants.length"
                       class="text-muted-foreground px-5 py-8 text-center text-sm">
                        Aucun enseignant. Attribuez ce module depuis la fiche d'un enseignant.
                    </p>
                </CarteSection>

                <!--=============================================================================================-->
                <!-- Séances : à venir, puis récentes -->
                <!--=============================================================================================-->
                <CarteSection titre="Séances" :avec_marges="false">
                    <div v-for="bloc in blocs_seances" :key="bloc.titre" class="border-b last:border-0">
                        <p class="text-muted-foreground bg-muted/40 px-5 py-2 text-xs font-medium">{{ bloc.titre }}</p>

                        <LigneSeance v-for="seance in bloc.seances" :key="seance.cle" :seance="seance"/>

                        <p v-if="!bloc.seances.length" class="text-muted-foreground px-5 py-4 text-sm">{{
                                bloc.vide
                            }}</p>
                    </div>
                </CarteSection>
            </div>

            <!--=================================================================================================-->
            <!-- En bref : reste visible au défilement -->
            <!--=================================================================================================-->
            <aside class="order-first lg:sticky lg:top-4 lg:order-none">
                <CarteSection titre="En bref" sous_titre="Année en cours">
                    <div class="flex flex-col gap-5">
                        <div class="flex flex-col gap-2">
                            <Indicateur
                                label="Avancement moyen"
                                :valeur="consultation.resume.avancement"
                                unite="%"
                                :detail="`${formater_nombre(consultation.resume.heures_moyennes)} h sur ${consultation.resume.volume} h par groupe`"
                            />
                            <BarreProgression :pourcentage="consultation.resume.avancement"/>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <Indicateur label="Séances tenues" :valeur="consultation.resume.nb_seances"/>
                            <Indicateur
                                label="Taux d'absence"
                                :valeur="formater_nombre(consultation.resume.taux)"
                                unite="%"
                                :tonalite="tonalite_taux(consultation.resume.taux)"
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
import {Head, Link, useForm} from '@inertiajs/vue3';
import {toast} from 'vue-sonner';
import {Button} from '@/components/ui/button';
import BarreProgression from '@/_core/detail/barre-progression.vue';
import CarteSection from '@/_core/detail/carte-section.vue';
import Indicateur from '@/_core/detail/indicateur.vue';
import LigneSeance from '@/_core/detail/ligne-seance.vue';
import {formater_nombre, tonalite_taux} from '@/_core/detail/taux';
import type {SeanceResume} from '@/_core/detail/types';
import {supprimer_avec_confirmation} from '@/_core/dialogs/actions';
import BadgeCouleur from '@/_core/renders/badge-couleur.vue';
import ChampChaine from '@/_core/renders/champ-chaine.vue';
import ChampNombre from '@/_core/renders/champ-nombre.vue';
import ChampSelect from '@/_core/renders/champ-select.vue';
import {renders, type ModeVue} from '@/_core/renders';
import type {SelectOption} from '@/_core/renders/types';
import CellulePersonne from '@/_core/table/cellule-personne.vue';

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

interface ConsultationModule {
    resume: {
        niveau_code: string;
        niveau_libelle: string;
        couleur: string;
        volume: number;
        heures_moyennes: number;
        avancement: number;
        nb_seances: number;
        nb_absences: number;
        nb_non_justifiees: number;
        taux: number;
    };
    avancement: { nom: string; heures: number; pourcentage: number; url: string }[];
    enseignants: { nom_complet: string; email: string; url: string }[];
    a_venir: SeanceResume[];
    recentes: SeanceResume[];
    liens: {
        niveau: string;
    };
}

interface ModuleDetailInterface {
    mode_vue: ModeVue;
    titre_page: string;
    item: Module;
    niveaux: NiveauOption[];
    consultation: ConsultationModule | null;
}

const props = defineProps<ModuleDetailInterface>();

const form = useForm({
    niveau_etude_id: props.item.niveau_etude_id ?? null,
    code: props.item.code ?? '',
    intitule: props.item.intitule ?? '',
    semestre: props.item.semestre ?? null,
    volume_horaire: props.item.volume_horaire ?? null,
});

const is_editable = computed(() => props.mode_vue === renders.mode_create || props.mode_vue === renders.mode_edit);

//==============================================================================================================
// Semestres proposés : de 1 au nombre de semestres du niveau choisi
//==============================================================================================================
const semestres_du_niveau = computed(() => {
    const niveau = props.niveaux.find((option) => option.valeur === form.niveau_etude_id);

    return Array.from({length: niveau?.nb_semestres ?? 0}, (_, index) => ({
        valeur: index + 1,
        label: `Semestre ${index + 1}`,
    }));
});

//==============================================================================================================
// Séances regroupées : à venir, puis récentes
//==============================================================================================================
const blocs_seances = computed(() => [
    {titre: 'À venir', seances: props.consultation?.a_venir ?? [], vide: 'Aucune séance planifiée à venir.'},
    {titre: 'Récentes', seances: props.consultation?.recentes ?? [], vide: 'Aucune séance passée.'},
]);

const url_list = '/modules';
const url_detail = props.item.cle ? `/module/${props.item.cle}` : '/module';
const url_annuler = props.mode_vue === renders.mode_edit ? url_detail : url_list;

const enregistrer = () =>
    form.post(url_detail, {
        preserveState: 'errors',
        onError: () => toast.error('Certains champs sont à corriger.'),
    });

const supprimer = () =>
    supprimer_avec_confirmation(
        url_detail,
        `Supprimer le module ${props.item.code} ?`,
        'Il sera archivé et ne sera plus proposé lors de la planification des séances.',
    );
</script>
