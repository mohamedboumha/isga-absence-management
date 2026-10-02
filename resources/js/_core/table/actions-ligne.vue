<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <button
                type="button"
                class="text-muted-foreground hover:bg-muted hover:text-foreground data-[state=open]:bg-muted flex size-8 items-center justify-center rounded-md"
                :aria-label="`Actions${actions.label ? ` pour ${actions.label}` : ''}`"
            >
                <MoreHorizontal class="size-4"/>
            </button>
        </DropdownMenuTrigger>

        <DropdownMenuContent align="end" class="w-44">
            <DropdownMenuItem @select="router.visit(actions.url_consulter)">
                <Eye class="size-4"/>
                Consulter
            </DropdownMenuItem>

            <DropdownMenuItem v-if="actions.url_modifier" @select="router.visit(actions.url_modifier)">
                <Pencil class="size-4"/>
                Modifier
            </DropdownMenuItem>

            <template v-if="actions.url_supprimer">
                <DropdownMenuSeparator/>

                <DropdownMenuItem class="text-absent focus:text-absent" @select="supprimer">
                    <Trash2 class="size-4"/>
                    Supprimer
                </DropdownMenuItem>
            </template>
        </DropdownMenuContent>
    </DropdownMenu>
</template>


<script setup lang="ts">
import {router} from '@inertiajs/vue3';
import {Eye, MoreHorizontal, Pencil, Trash2} from '@lucide/vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {supprimer_avec_confirmation} from '@/_core/dialogs/actions';
import type {TableActions} from './types';

interface ActionsLigneInterface {
    actions: TableActions;
}

const props = defineProps<ActionsLigneInterface>();

//==============================================================================================================
// Suppression : même dialogue de confirmation que sur la fiche
//==============================================================================================================
const supprimer = () => {
    if (!props.actions.url_supprimer) return;

    supprimer_avec_confirmation(
        props.actions.url_supprimer,
        props.actions.label ? `Supprimer « ${props.actions.label} » ?` : 'Supprimer cet élément ?',
        "Il sera archivé et n'apparaîtra plus dans les listes.",
    );
};
</script>
