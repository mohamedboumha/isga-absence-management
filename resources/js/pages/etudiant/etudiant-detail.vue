<template>
    <Head :title="titre_page" />

    <div class="flex max-w-2xl flex-col gap-6 p-4">
        <!--=====================================================================================================-->
        <!-- En-tête -->
        <!--=====================================================================================================-->
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold">{{ titre_page }}</h1>

            <div
                v-if="mode_vue === renders.mode_consultation"
                class="flex gap-2"
            >
                <Button as-child variant="outline">
                    <a
                        :href="`${url_detail}/releve`"
                        target="_blank"
                        rel="noopener"
                        >Relevé PDF</a
                    >
                </Button>

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
            <div class="grid grid-cols-2 gap-4">
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

                <p v-else class="text-muted-foreground self-end text-sm">
                    Aucune année universitaire active : l'inscription se fera une fois une année activée.
                </p>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <ChampChaine
                    :mode_vue="mode_vue"
                    nom_champ="nom"
                    label="Nom"
                    required
                    v-model:valeur="form.nom"
                    :error="form.errors.nom"
                />

                <ChampChaine
                    :mode_vue="mode_vue"
                    nom_champ="prenom"
                    label="Prénom"
                    required
                    v-model:valeur="form.prenom"
                    :error="form.errors.prenom"
                />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <ChampChaine
                    :mode_vue="mode_vue"
                    nom_champ="email"
                    label="E-mail"
                    placeholder="prenom.nom@exemple.ma"
                    v-model:valeur="form.email"
                    :error="form.errors.email"
                />

                <ChampChaine
                    :mode_vue="mode_vue"
                    nom_champ="telephone"
                    label="Téléphone"
                    placeholder="0612345678"
                    v-model:valeur="form.telephone"
                    :error="form.errors.telephone"
                />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <ChampDate
                    :mode_vue="mode_vue"
                    nom_champ="date_naissance"
                    label="Date de naissance"
                    v-model:valeur="form.date_naissance"
                    :error="form.errors.date_naissance"
                />
            </div>

            <!--=================================================================================================-->
            <!-- Boutons (create / edit uniquement) -->
            <!--=================================================================================================-->
            <div v-if="is_editable" class="flex gap-2">
                <Button type="submit" :disabled="form.processing"
                    >Enregistrer</Button
                >

                <Button as-child variant="outline">
                    <Link :href="url_annuler">Annuler</Link>
                </Button>
            </div>
        </form>

        <!--=====================================================================================================-->
        <!-- Historique des inscriptions (BF-37) -->
        <!--=====================================================================================================-->
        <div v-if="item.cle" class="flex flex-col gap-2">
            <h2 class="font-medium">Parcours de l'étudiant</h2>

            <div class="overflow-x-auto rounded-lg border">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50 text-left">
                    <tr>
                        <th class="px-4 py-2 font-medium">Année</th>
                        <th class="px-4 py-2 font-medium">Groupe</th>
                        <th class="px-4 py-2 font-medium">Niveau d'études</th>
                        <th class="px-4 py-2 font-medium">Décision</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="ligne in historique" :key="ligne.annee" class="border-t">
                        <td class="px-4 py-2">
                            {{ ligne.annee }}
                            <StatutPill v-if="ligne.active" statut="en_cours" class="ml-2" />
                        </td>
                        <td class="px-4 py-2">{{ ligne.groupe }}</td>
                        <td class="px-4 py-2">{{ ligne.niveau }}</td>
                        <td class="px-4 py-2">
                            <StatutPill v-if="ligne.decision" :statut="ligne.decision" />
                            <span v-else class="text-muted-foreground">—</span>
                        </td>
                    </tr>
                    <tr v-if="!historique.length">
                        <td colspan="4" class="text-muted-foreground px-4 py-6 text-center">Aucune inscription pour le moment.</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import ChampChaine from '@/_core/renders/champ-chaine.vue';
import ChampDate from '@/_core/renders/champ-date.vue';
import ChampSelect from '@/_core/renders/champ-select.vue';
import { renders, type ModeVue } from '@/_core/renders';
import type { SelectOption } from '@/_core/renders/types';
import { supprimer_avec_confirmation } from '@/_core/dialogs/actions';
import StatutPill from '@/_core/renders/statut-pill.vue';

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

interface EtudiantDetailInterface {
    mode_vue: ModeVue;
    titre_page: string;
    item: Etudiant;
    groupes: SelectOption[];
    annee_active: string | null;
    historique: LigneHistorique[];
}

const props = defineProps<EtudiantDetailInterface>();

//==============================================================================================================
// Formulaire
//==============================================================================================================
const form = useForm({
    groupe_id: props.item.groupe_id ?? null,
    cne: props.item.cne ?? '',
    nom: props.item.nom ?? '',
    prenom: props.item.prenom ?? '',
    email: props.item.email ?? '',
    telephone: props.item.telephone ?? '',
    date_naissance: props.item.date_naissance ?? '',
});

const is_editable = computed(
    () =>
        props.mode_vue === renders.mode_create ||
        props.mode_vue === renders.mode_edit,
);

//==============================================================================================================
// URLs
//==============================================================================================================
const url_list = '/etudiants';
const url_detail = props.item.cle ? `/etudiant/${props.item.cle}` : '/etudiant';
const url_annuler =
    props.mode_vue === renders.mode_edit ? url_detail : url_list;

//==============================================================================================================
// Actions
//==============================================================================================================
const enregistrer = () => form.post(url_detail, { preserveState: 'errors' });

const supprimer = () =>
    supprimer_avec_confirmation(
        url_detail,
        `Supprimer l'étudiant ${props.item.prenom} ${props.item.nom} ?`,
        "Sa fiche sera archivée et n'apparaîtra plus dans les listes.",
    );
</script>
