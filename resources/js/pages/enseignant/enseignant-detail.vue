<template>
    <Head :title="titre_page" />

    <div class="flex max-w-2xl flex-col gap-6 p-4">
        <!--=====================================================================================================-->
        <!-- En-tête -->
        <!--=====================================================================================================-->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold">{{ titre_page }}</h1>

                <p
                    v-if="mode_vue === renders.mode_consultation"
                    class="text-sm text-muted-foreground"
                >
                    Compte de connexion :
                    <span
                        :class="
                            item.compte_actif
                                ? 'text-green-700'
                                : 'text-destructive'
                        "
                    >
                        {{ item.compte_actif ? 'actif' : 'désactivé' }}
                    </span>
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

        <p
            v-if="mode_vue === renders.mode_create"
            class="rounded-md bg-muted p-3 text-sm"
        >
            Un compte de connexion sera créé avec cet e-mail. L'enseignant
            recevra un lien pour choisir son mot de passe.
        </p>

        <!--=====================================================================================================-->
        <!-- Champs -->
        <!--=====================================================================================================-->
        <form class="grid gap-4" @submit.prevent="enregistrer">
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
                    placeholder="prenom.nom@isga.ma"
                    required
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

            <ChampMultiSelect
                :mode_vue="mode_vue"
                nom_champ="modules"
                label="Modules enseignés"
                :options="modules"
                v-model:valeur="form.modules"
                :error="form.errors.modules"
            />

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
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import ChampChaine from '@/_core/renders/champ-chaine.vue';
import ChampMultiSelect from '@/_core/renders/champ-multi-select.vue';
import { renders, type ModeVue } from '@/_core/renders';
import type { SelectOption } from '@/_core/renders/types';
import { supprimer_avec_confirmation } from '@/_core/dialogs/actions';

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

interface EnseignantDetailInterface {
    mode_vue: ModeVue;
    titre_page: string;
    item: Enseignant;
    modules: SelectOption[];
}

const props = defineProps<EnseignantDetailInterface>();

//==============================================================================================================
// Formulaire
//==============================================================================================================
const form = useForm({
    nom: props.item.nom ?? '',
    prenom: props.item.prenom ?? '',
    email: props.item.email ?? '',
    telephone: props.item.telephone ?? '',
    modules: props.item.modules ?? [],
});

const is_editable = computed(
    () =>
        props.mode_vue === renders.mode_create ||
        props.mode_vue === renders.mode_edit,
);

//==============================================================================================================
// URLs
//==============================================================================================================
const url_list = '/enseignants';
const url_detail = props.item.cle
    ? `/enseignant/${props.item.cle}`
    : '/enseignant';
const url_annuler =
    props.mode_vue === renders.mode_edit ? url_detail : url_list;

//==============================================================================================================
// Actions
//==============================================================================================================
const enregistrer = () => form.post(url_detail, { preserveState: 'errors' });

const supprimer = () =>
    supprimer_avec_confirmation(
        url_detail,
        `Supprimer l'enseignant ${props.item.prenom} ${props.item.nom} ?`,
        'Sa fiche sera archivée et son compte de connexion désactivé.',
    );
</script>
