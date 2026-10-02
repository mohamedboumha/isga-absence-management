<template>
    <Head title="Mot de passe"/>

    <CarteSection titre="Changer de mot de passe"
                  sous_titre="Choisissez un mot de passe long, que vous n'utilisez nulle part ailleurs">
        <form class="grid gap-4" @submit.prevent="enregistrer">
            <div class="grid gap-4 md:grid-cols-2">
                <div class="md:col-span-2 md:max-w-[calc(50%-0.5rem)]">
                    <ChampChaine
                        :mode_vue="renders.mode_edit"
                        nom_champ="current_password"
                        type="password"
                        label="Mot de passe actuel"
                        autocomplete="current-password"
                        required
                        v-model:valeur="form.current_password"
                        :error="form.errors.current_password"
                    />
                </div>

                <ChampChaine
                    :mode_vue="renders.mode_edit"
                    nom_champ="password"
                    type="password"
                    label="Nouveau mot de passe"
                    autocomplete="new-password"
                    required
                    v-model:valeur="form.password"
                    :error="form.errors.password"
                />

                <ChampChaine
                    :mode_vue="renders.mode_edit"
                    nom_champ="password_confirmation"
                    type="password"
                    label="Confirmer le nouveau mot de passe"
                    autocomplete="new-password"
                    required
                    v-model:valeur="form.password_confirmation"
                    :error="form.errors.password_confirmation"
                />
            </div>

            <div>
                <Button type="submit" :disabled="form.processing" data-test="update-password-button">Changer le mot de
                    passe
                </Button>
            </div>
        </form>
    </CarteSection>
</template>


<script setup lang="ts">
import {Head, useForm} from '@inertiajs/vue3';
import {toast} from 'vue-sonner';
import {Button} from '@/components/ui/button';
import CarteSection from '@/_core/detail/carte-section.vue';
import ChampChaine from '@/_core/renders/champ-chaine.vue';
import {renders} from '@/_core/renders';

interface SecurityInterface {
    passwordRules?: string;
}

const props = defineProps<SecurityInterface>();

defineOptions({
    layout: {
        breadcrumbs: [{title: 'Mon compte', href: '/settings/security'}],
    },
});

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

//==============================================================================================================
// Succès : formulaire vidé + message ; erreur : les mots de passe sont vidés, pas les messages
//==============================================================================================================
const enregistrer = () =>
    form.put('/settings/password', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            toast.success('Mot de passe changé.');
        },
        onError: () => form.reset('current_password', 'password', 'password_confirmation'),
    });
</script>
