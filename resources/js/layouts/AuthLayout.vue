<template>
    <div class="bg-background grid min-h-svh lg:grid-cols-[minmax(0,1.1fr)_minmax(0,1fr)]">
        <!--=====================================================================================================-->
        <!-- Côté identité (grand écran) : logo, registre, une phrase -->
        <!--=====================================================================================================-->
        <aside class="bg-sidebar hidden flex-col justify-between gap-10 border-r p-10 lg:flex xl:p-14">
            <img src="/images/logo-isga.png" alt="ISGA — Institut supérieur d'ingénierie et des affaires" class="h-14 w-auto self-start" />

            <RegistreIllustration class="self-center" />

            <p class="text-muted-foreground max-w-sm text-sm leading-relaxed">
                Faites l'appel en quelques secondes et suivez les absences de chaque étudiant tout au long de l'année.
            </p>
        </aside>

        <!--=====================================================================================================-->
        <!-- Formulaire -->
        <!--=====================================================================================================-->
        <main class="flex items-center justify-center p-6 sm:p-10">
            <div class="w-full max-w-sm">
                <img src="/images/logo-isga.png" alt="ISGA" class="mb-10 h-11 w-auto lg:hidden" />

                <h1 v-if="title" class="text-2xl font-semibold tracking-tight">{{ title }}</h1>
                <p v-if="description" class="text-muted-foreground mt-1.5 text-sm">{{ description }}</p>

                <div class="mt-8">
                    <slot />
                </div>

                <p class="text-muted-foreground mt-12 text-xs">Un problème pour vous connecter ? Contactez le service de scolarité.</p>
            </div>
        </main>
    </div>
</template>


<script setup lang="ts">
import RegistreIllustration from '@/_core/auth/registre-illustration.vue';

interface AuthLayoutInterface {
    title?: string;
    description?: string;
}

const props = defineProps<AuthLayoutInterface>();
</script>
