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
