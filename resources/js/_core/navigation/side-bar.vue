<template>
    <SidebarGroup
        v-for="(groupe, index) in groupes"
        :key="groupe.label ?? index"
        class="px-2 py-0"
    >
        <SidebarGroupLabel v-if="groupe.label">{{
            groupe.label
        }}</SidebarGroupLabel>

        <SidebarMenu>
            <SidebarMenuItem v-for="item in groupe.items" :key="item.href">
                <SidebarMenuButton
                    as-child
                    :is-active="isCurrentUrl(item.href)"
                    :tooltip="item.label"
                    class="relative data-[active=true]:font-medium data-[active=true]:shadow-xs data-[active=true]:before:absolute data-[active=true]:before:inset-y-1.5 data-[active=true]:before:left-0 data-[active=true]:before:w-[3px] data-[active=true]:before:rounded-full data-[active=true]:before:bg-isga-rouge"
                >
                    <Link :href="item.href">
                        <component :is="get_icon(item.icon)" />
                        <span>{{ item.label }}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { get_icon } from './icons';
import type { SideBarGroupe } from './types';

const page = usePage();
const { isCurrentUrl } = useCurrentUrl();

//==============================================================================================================
// Groupes déjà filtrés par rôle côté serveur (SideBarService)
//==============================================================================================================
const groupes = computed(
    () => (page.props.sidebar as SideBarGroupe[] | undefined) ?? [],
);
</script>
