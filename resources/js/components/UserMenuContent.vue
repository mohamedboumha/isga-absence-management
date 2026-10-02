<template>
    <!--=========================================================================================================-->
    <!-- La personne connectée -->
    <!--=========================================================================================================-->
    <DropdownMenuLabel class="p-0 font-normal">
        <div class="flex items-center gap-3 px-2 py-2">
            <span
                class="bg-isga-gris flex size-9 shrink-0 items-center justify-center rounded-full text-sm font-semibold text-white"
                aria-hidden="true">
                {{ initiales }}
            </span>

            <span class="grid min-w-0 leading-tight">
                <span class="truncate text-sm font-medium">{{ nom_complet }}</span>
                <span class="text-muted-foreground truncate text-xs">{{ email }}</span>
                <span class="text-muted-foreground truncate text-xs">{{ libelle_role }}</span>
            </span>
        </div>
    </DropdownMenuLabel>

    <DropdownMenuSeparator/>

    <DropdownMenuGroup>
        <DropdownMenuItem :as-child="true">
            <Link class="flex w-full cursor-pointer items-center" href="/settings/profile">
                <UserRound class="mr-2 size-4"/>
                Mon compte
            </Link>
        </DropdownMenuItem>
    </DropdownMenuGroup>

    <!--=========================================================================================================-->
    <!-- Apparence : choisie directement dans le menu -->
    <!--=========================================================================================================-->
    <DropdownMenuSeparator/>

    <div class="flex flex-col gap-1.5 px-2 py-1.5">
        <span class="text-muted-foreground text-xs">Apparence</span>
        <ChoixApparence/>
    </div>

    <DropdownMenuSeparator/>

    <DropdownMenuItem :as-child="true">
        <Link class="flex w-full cursor-pointer items-center" :href="logout()" as="button" data-test="logout-button"
              @click="se_deconnecter">
            <LogOut class="mr-2 size-4"/>
            Se déconnecter
        </Link>
    </DropdownMenuItem>
</template>


<script setup lang="ts">
import {Link, router} from '@inertiajs/vue3';
import {LogOut, UserRound} from '@lucide/vue';
import {
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator
} from '@/components/ui/dropdown-menu';
import ChoixApparence from '@/_core/navigation/choix-apparence.vue';
import {logout} from '@/routes';

interface UserMenuContentInterface {
    nom_complet: string;
    email: string;
    libelle_role: string;
    initiales: string;
}

const props = defineProps<UserMenuContentInterface>();

//==============================================================================================================
// Déconnexion : on vide le cache des pages, pour qu'un autre utilisateur ne voie rien du précédent
//==============================================================================================================
const se_deconnecter = () => {
    router.flushAll();
};
</script>
