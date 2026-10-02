<template>
    <Head title="Connexion"/>

    <!--=========================================================================================================-->
    <!-- Message après une action (ex. mot de passe réinitialisé) -->
    <!--=========================================================================================================-->
    <p v-if="status" class="bg-present/10 text-present ring-present/25 mb-6 rounded-md p-3 text-sm ring-1 ring-inset">
        {{ status }}
    </p>

    <form class="flex flex-col gap-5" @submit.prevent="se_connecter">
        <ChampChaine
            :mode_vue="renders.mode_edit"
            nom_champ="email"
            type="email"
            label="Adresse e-mail"
            placeholder="prenom.nom@isga.ma"
            autocomplete="username"
            autofocus
            required
            v-model:valeur="form.email"
            :error="form.errors.email"
        />

        <ChampChaine
            :mode_vue="renders.mode_edit"
            nom_champ="password"
            type="password"
            label="Mot de passe"
            autocomplete="current-password"
            required
            v-model:valeur="form.password"
            :error="form.errors.password"
        >
            <template v-if="canResetPassword" #label-action>
                <Link href="/forgot-password"
                      class="text-muted-foreground hover:text-foreground text-sm underline-offset-4 hover:underline">
                    Mot de passe oublié ?
                </Link>
            </template>
        </ChampChaine>

        <ChampBoolean
            :mode_vue="renders.mode_edit"
            nom_champ="remember"
            label="Rester connecté sur cet appareil"
            v-model:valeur="form.remember"
        />

        <Button type="submit" class="h-11" :disabled="form.processing">
            <Spinner v-if="form.processing" class="size-4"/>
            Se connecter
        </Button>
    </form>
</template>


<script setup lang="ts">
import {Head, Link, useForm} from '@inertiajs/vue3';
import {Button} from '@/components/ui/button';
import {Spinner} from '@/components/ui/spinner';
import ChampBoolean from '@/_core/renders/champ-boolean.vue';
import ChampChaine from '@/_core/renders/champ-chaine.vue';
import {renders} from '@/_core/renders';

interface LoginInterface {
    status?: string;
    canResetPassword: boolean;
}

const props = defineProps<LoginInterface>();

defineOptions({
    layout: {
        title: 'Connexion',
        description: 'Connectez-vous avec votre adresse e-mail ISGA.',
    },
});

//==============================================================================================================
// Formulaire de connexion (Fortify : POST /login)
//==============================================================================================================
const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const se_connecter = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
};
</script>
