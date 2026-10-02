<template>
    <Head title="Nouveau mot de passe"/>

    <Form
        v-bind="update.form()"
        :transform="(data) => ({ ...data, token, email })"
        :reset-on-success="['password', 'password_confirmation']"
        class="flex flex-col gap-5"
        v-slot="{ errors, processing }"
    >
        <!--=====================================================================================================-->
        <!-- Le compte concerné (lecture seule) -->
        <!--=====================================================================================================-->
        <div class="grid gap-2">
            <Label for="email">Adresse e-mail</Label>
            <Input id="email" type="email" name="email" autocomplete="username" :model-value="email" readonly
                   class="bg-muted/40 text-muted-foreground h-11 border-transparent"/>
            <InputError :message="errors.email"/>
        </div>

        <!--=====================================================================================================-->
        <!-- Nouveau mot de passe -->
        <!--=====================================================================================================-->
        <div class="grid gap-2">
            <Label for="password">Nouveau mot de passe</Label>
            <PasswordInput
                id="password"
                name="password"
                autocomplete="new-password"
                autofocus
                required
                class="bg-muted/60 h-11 border-transparent"
                :passwordrules="passwordRules"
                :aria-invalid="Boolean(errors.password)"
            />
            <InputError :message="errors.password"/>
        </div>

        <div class="grid gap-2">
            <Label for="password_confirmation">Confirmer le mot de passe</Label>
            <PasswordInput
                id="password_confirmation"
                name="password_confirmation"
                autocomplete="new-password"
                required
                class="bg-muted/60 h-11 border-transparent"
                :passwordrules="passwordRules"
                :aria-invalid="Boolean(errors.password_confirmation)"
            />
            <InputError :message="errors.password_confirmation"/>
        </div>

        <Button type="submit" class="h-11" :disabled="processing" data-test="reset-password-button">
            <Spinner v-if="processing" class="size-4"/>
            Enregistrer mon mot de passe
        </Button>
    </Form>
</template>


<script setup lang="ts">
import {Form, Head} from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import {Button} from '@/components/ui/button';
import {Input} from '@/components/ui/input';
import {Label} from '@/components/ui/label';
import {Spinner} from '@/components/ui/spinner';
import {update} from '@/routes/password';

interface ResetPasswordInterface {
    token: string;
    email: string;
    passwordRules: string;
}

const props = defineProps<ResetPasswordInterface>();

defineOptions({
    layout: {
        title: 'Choisissez votre mot de passe',
        description: 'Il vous servira à vous connecter à l\'application de gestion des absences.',
    },
});
</script>
