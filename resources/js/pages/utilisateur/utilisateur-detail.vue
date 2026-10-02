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
                    <StatutPill :statut="item.actif ? 'actif' : 'desactive'"/>
                    <span class="text-muted-foreground">Compte créé le {{ item.cree_le }}</span>
                    <span v-if="item.is_moi" class="font-medium">C'est votre compte</span>
                </div>
            </div>

            <div v-if="mode_vue === renders.mode_consultation" class="flex flex-wrap gap-2">
                <Button variant="outline" :disabled="envoi_en_cours" @click="envoyer_lien">Renvoyer le lien</Button>

                <Button as-child variant="outline">
                    <Link :href="`${url_detail}/edit`">Modifier</Link>
                </Button>
            </div>
        </div>

        <p v-if="mode_vue === renders.mode_create" class="bg-champ rounded-md border p-3 text-sm">
            La personne recevra un e-mail avec un lien pour choisir son mot de passe.
        </p>

        <p v-if="item.is_enseignant" class="bg-champ rounded-md border p-3 text-sm">
            Compte enseignant : le nom et l'e-mail se modifient depuis
            <Link v-if="item.url_enseignant" :href="item.url_enseignant" class="underline underline-offset-4">sa fiche
                enseignant
            </Link>
            <template v-else>sa fiche enseignant</template>
            .
            Ici, seul l'état du compte (actif ou désactivé) peut être changé.
        </p>

        <!--=====================================================================================================-->
        <!-- Informations -->
        <!--=====================================================================================================-->
        <CarteSection titre="Informations">
            <form class="grid gap-4" @submit.prevent="enregistrer">
                <div class="grid gap-4 md:grid-cols-2">
                    <ChampChaine
                        :mode_vue="mode_champs_identite"
                        nom_champ="name"
                        label="Nom"
                        required
                        v-model:valeur="form.name"
                        :error="form.errors.name"
                    />

                    <ChampChaine
                        :mode_vue="mode_champs_identite"
                        nom_champ="prenom"
                        label="Prénom"
                        required
                        v-model:valeur="form.prenom"
                        :error="form.errors.prenom"
                    />

                    <ChampChaine
                        :mode_vue="mode_champs_identite"
                        nom_champ="email"
                        type="email"
                        label="E-mail"
                        required
                        v-model:valeur="form.email"
                        :error="form.errors.email"
                    />

                    <ChampSelect
                        :mode_vue="mode_champs_identite"
                        nom_champ="role"
                        label="Rôle"
                        required
                        :options="roles"
                        v-model:valeur="form.role"
                        :error="form.errors.role"
                    />

                    <ChampBoolean
                        :mode_vue="mode_vue"
                        nom_champ="actif"
                        label="Compte actif"
                        label_oui="Actif"
                        label_non="Désactivé"
                        v-model:valeur="form.actif"
                        :error="form.errors.actif"
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
    </div>
</template>


<script setup lang="ts">
import {computed, ref} from 'vue';
import {Head, Link, router, useForm} from '@inertiajs/vue3';
import {toast} from 'vue-sonner';
import {Button} from '@/components/ui/button';
import CarteSection from '@/_core/detail/carte-section.vue';
import {demander_confirmation} from '@/_core/dialogs/confirmation';
import ChampBoolean from '@/_core/renders/champ-boolean.vue';
import ChampChaine from '@/_core/renders/champ-chaine.vue';
import ChampSelect from '@/_core/renders/champ-select.vue';
import StatutPill from '@/_core/renders/statut-pill.vue';
import {renders, type ModeVue} from '@/_core/renders';
import type {SelectOption} from '@/_core/renders/types';

interface Utilisateur {
    cle: string | null;
    name: string | null;
    prenom: string | null;
    email: string | null;
    role: string | null;
    actif: boolean;
    is_enseignant: boolean;
    is_moi: boolean;
    url_enseignant: string | null;
    cree_le: string | null;
}

interface UtilisateurDetailInterface {
    mode_vue: ModeVue;
    titre_page: string;
    item: Utilisateur;
    roles: SelectOption[];
}

const props = defineProps<UtilisateurDetailInterface>();

const form = useForm({
    name: props.item.name ?? '',
    prenom: props.item.prenom ?? '',
    email: props.item.email ?? '',
    role: props.item.role ?? null,
    actif: props.item.actif ?? true,
});

const is_editable = computed(() => props.mode_vue === renders.mode_create || props.mode_vue === renders.mode_edit);

//==============================================================================================================
// Compte enseignant : identité et rôle en lecture seule, même en modification
//==============================================================================================================
const mode_champs_identite = computed<ModeVue>(() => (props.item.is_enseignant && is_editable.value ? renders.mode_consultation : props.mode_vue));

const url_list = '/utilisateurs';
const url_detail = props.item.cle ? `/utilisateur/${props.item.cle}` : '/utilisateur';
const url_annuler = props.mode_vue === renders.mode_edit ? url_detail : url_list;

const envoi_en_cours = ref(false);

const enregistrer = () =>
    form.post(url_detail, {
        preserveState: 'errors',
        onError: () => toast.error('Certains champs sont à corriger.'),
    });

const envoyer_lien = async () => {
    const {confirme} = await demander_confirmation({
        titre: 'Envoyer un lien de mot de passe ?',
        message: `${props.item.email} recevra un e-mail pour choisir un nouveau mot de passe.`,
        bouton: 'Envoyer le lien',
    });

    if (!confirme) return;

    envoi_en_cours.value = true;

    router.post(`${url_detail}/lien-mot-de-passe`, {}, {onFinish: () => (envoi_en_cours.value = false)});
};
</script>
