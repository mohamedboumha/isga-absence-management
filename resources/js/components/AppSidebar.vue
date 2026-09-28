<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo/>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <SideBar/>
        </SidebarContent>

        <SidebarFooter>
            <NavUser/>
        </SidebarFooter>
    </Sidebar>
    <slot/>
</template>


<script setup lang="ts">
import {Link} from '@inertiajs/vue3';
import SideBar from '@/_core/navigation/side-bar.vue';
import AppLogo from '@/components/AppLogo.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import {dashboard} from '@/routes';
</script>
