<template>
    <Head title="Mot de passe oublié"/>

    <!--=========================================================================================================-->
    <!-- Lien envoyé -->
    <!--=========================================================================================================-->
    <p v-if="status" class="bg-present/10 text-present ring-present/25 mb-6 rounded-md p-3 text-sm ring-1 ring-inset">
        {{ status }}
    </p>

    <Form v-bind="email.form()" class="flex flex-col gap-5" v-slot="{ errors, processing }">
        <div class="grid gap-2">
            <Label for="email">Adresse e-mail</Label>
            <Input
                id="email"
                v-focus
                type="email"
                name="email"
                autocomplete="username"
                placeholder="prenom.nom@isga.ma"
                required
                class="bg-muted/60 h-11 border-transparent"
                :aria-invalid="Boolean(errors.email)"
            />
            <InputError :message="errors.email"/>
        </div>

        <Button type="submit" class="h-11" :disabled="processing" data-test="email-password-reset-link-button">
            <Spinner v-if="processing" class="size-4"/>
            Recevoir le lien
        </Button>
    </Form>

    <p class="text-muted-foreground mt-6 text-sm">
        Vous vous en souvenez ?
        <Link :href="login()" class="text-foreground font-medium underline-offset-4 hover:underline">Se connecter</Link>
    </p>
</template>


<script setup lang="ts">
import {Form, Head, Link} from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import {Button} from '@/components/ui/button';
import {Input} from '@/components/ui/input';
import {Label} from '@/components/ui/label';
import {Spinner} from '@/components/ui/spinner';
import {login} from '@/routes';
import {email} from '@/routes/password';

interface ForgotPasswordInterface {
    status?: string;
}

const props = defineProps<ForgotPasswordInterface>();

defineOptions({
    layout: {
        title: 'Mot de passe oublié',
        description: 'Indiquez votre adresse e-mail : vous recevrez un lien pour choisir un nouveau mot de passe.',
    },
});
</script>
