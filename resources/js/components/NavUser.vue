<template>
    <SidebarMenu>
        <SidebarMenuItem>
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <SidebarMenuButton
                        size="lg"
                        class="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
                        data-test="sidebar-menu-button"
                    >
                        <span
                            class="bg-isga-gris flex size-8 shrink-0 items-center justify-center rounded-full text-xs font-semibold text-white"
                            aria-hidden="true"
                        >
                            {{ initiales }}
                        </span>

                        <span class="grid min-w-0 flex-1 text-left leading-tight">
                            <span class="truncate text-sm font-medium">{{ nom_complet }}</span>
                            <span class="text-muted-foreground truncate text-xs">{{ libelle_role }}</span>
                        </span>

                        <ChevronsUpDown class="ml-auto size-4 opacity-60"/>
                    </SidebarMenuButton>
                </DropdownMenuTrigger>

                <DropdownMenuContent
                    class="w-(--reka-dropdown-menu-trigger-width) min-w-64 rounded-lg"
                    :side="isMobile ? 'bottom' : state === 'collapsed' ? 'left' : 'top'"
                    align="end"
                    :side-offset="6"
                >
                    <UserMenuContent :nom_complet="nom_complet" :email="user.email" :libelle_role="libelle_role"
                                     :initiales="initiales"/>
                </DropdownMenuContent>
            </DropdownMenu>
        </SidebarMenuItem>
    </SidebarMenu>
</template>


<script setup lang="ts">
import {computed} from 'vue';
import {usePage} from '@inertiajs/vue3';
import {ChevronsUpDown} from '@lucide/vue';
import {DropdownMenu, DropdownMenuContent, DropdownMenuTrigger} from '@/components/ui/dropdown-menu';
import {SidebarMenu, SidebarMenuButton, SidebarMenuItem, useSidebar} from '@/components/ui/sidebar';
import UserMenuContent from '@/components/UserMenuContent.vue';

interface UtilisateurConnecte {
    name: string;
    prenom: string | null;
    email: string;
    role: string;
}

const libelles_roles: Record<string, string> = {
    super_admin: 'Super-administrateur',
    admin: 'Administrateur',
    enseignant: 'Enseignant',
};

const page = usePage();
const user = computed(() => page.props.auth.user as unknown as UtilisateurConnecte);

const {isMobile, state} = useSidebar();

//==============================================================================================================
// "Salma EL IDRISSI", "SE", "Administrateur"
//==============================================================================================================
const nom_complet = computed(() => [user.value.prenom, user.value.name?.toUpperCase()].filter(Boolean).join(' '));

const initiales = computed(() =>
    nom_complet.value
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((mot) => mot.charAt(0).toUpperCase())
        .join(''),
);

const libelle_role = computed(() => libelles_roles[user.value.role] ?? user.value.role);
</script>
