<template>
    <div class="flex flex-col gap-6 p-4">
        <div>
            <h1 class="text-xl font-semibold">Mon compte</h1>
            <p class="text-muted-foreground text-sm">Vos informations, votre mot de passe et l'apparence de
                l'application.</p>
        </div>

        <div class="flex flex-col gap-6 lg:flex-row">
            <!--=================================================================================================-->
            <!-- Rubriques -->
            <!--=================================================================================================-->
            <nav class="flex gap-1 overflow-x-auto lg:w-56 lg:shrink-0 lg:flex-col" aria-label="Mon compte">
                <Link
                    v-for="rubrique in rubriques"
                    :key="rubrique.href"
                    :href="rubrique.href"
                    class="flex shrink-0 items-center gap-2.5 rounded-md px-3 py-2 text-sm transition-colors"
                    :class="isCurrentOrParentUrl(rubrique.href) ? 'bg-card text-foreground border font-medium' : 'text-muted-foreground hover:bg-champ hover:text-foreground border border-transparent'"
                    :aria-current="isCurrentOrParentUrl(rubrique.href) ? 'page' : undefined"
                >
                    <component :is="rubrique.icone" class="size-4"/>
                    {{ rubrique.label }}
                </Link>
            </nav>

            <!--=================================================================================================-->
            <!-- Contenu de la rubrique -->
            <!--=================================================================================================-->
            <div class="flex min-w-0 flex-1 flex-col gap-6 lg:max-w-3xl">
                <slot/>
            </div>
        </div>
    </div>
</template>


<script setup lang="ts">
import {Link} from '@inertiajs/vue3';
import {KeyRound, Palette, UserRound} from '@lucide/vue';
import {useCurrentUrl} from '@/composables/useCurrentUrl';

const {isCurrentOrParentUrl} = useCurrentUrl();

const rubriques = [
    {label: 'Profil', href: '/settings/profile', icone: UserRound},
    {label: 'Mot de passe', href: '/settings/security', icone: KeyRound},
    {label: 'Apparence', href: '/settings/appearance', icone: Palette},
];
</script>
