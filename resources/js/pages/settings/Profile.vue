<template>
    <Head title="Profil"/>

    <CarteSection titre="Profil" sous_titre="Vos informations de connexion">
        <div class="grid gap-4 md:grid-cols-2">
            <ChampChaine :mode_vue="renders.mode_consultation" nom_champ="name" label="Nom" :valeur="user.name"/>
            <ChampChaine :mode_vue="renders.mode_consultation" nom_champ="prenom" label="Prénom"
                         :valeur="user.prenom ?? ''"/>
            <ChampChaine :mode_vue="renders.mode_consultation" nom_champ="email" label="E-mail" :valeur="user.email"/>
            <ChampChaine :mode_vue="renders.mode_consultation" nom_champ="role" label="Rôle"
                         :valeur="libelles_roles[user.role] ?? user.role"/>
        </div>

        <p class="bg-champ mt-5 rounded-md border p-3 text-sm">
            Vos informations sont gérées par l'administration de l'ISGA. Pour corriger votre nom ou votre adresse
            e-mail, contactez le service de scolarité.
        </p>
    </CarteSection>
</template>


<script setup lang="ts">
import {computed} from 'vue';
import {Head, usePage} from '@inertiajs/vue3';
import CarteSection from '@/_core/detail/carte-section.vue';
import ChampChaine from '@/_core/renders/champ-chaine.vue';
import {renders} from '@/_core/renders';

interface UtilisateurConnecte {
    name: string;
    prenom: string | null;
    email: string;
    role: string;
}

defineOptions({
    layout: {
        breadcrumbs: [{title: 'Mon compte', href: '/settings/profile'}],
    },
});

const libelles_roles: Record<string, string> = {
    super_admin: 'Super-administrateur',
    admin: 'Administrateur',
    enseignant: 'Enseignant',
};

const page = usePage();
const user = computed(() => page.props.auth.user as unknown as UtilisateurConnecte);
</script>
