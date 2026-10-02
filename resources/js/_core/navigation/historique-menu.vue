<template>
    <DropdownMenu @update:open="(ouvert) => ouvert && (maintenant = Date.now())">
        <DropdownMenuTrigger as-child>
            <button
                type="button"
                class="bg-champ text-muted-foreground hover:text-foreground data-[state=open]:text-foreground inline-flex shrink-0 items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-medium transition-colors"
                aria-label="Pages récentes"
            >
                <History class="size-3.5"/>
                <span class="hidden sm:inline">Historique</span>
            </button>
        </DropdownMenuTrigger>

        <DropdownMenuContent align="end" :side-offset="6" class="w-80 p-1.5">
            <DropdownMenuLabel class="text-muted-foreground px-2 py-1.5 text-xs font-medium">Pages récentes
            </DropdownMenuLabel>

            <!--=================================================================================================-->
            <!-- Les pages, la plus récente en premier -->
            <!--=================================================================================================-->
            <div class="max-h-96 overflow-y-auto">
                <DropdownMenuItem v-for="entree in etat_historique.entrees" :key="entree.chemin" as-child
                                  class="cursor-pointer gap-3 px-2 py-2">
                    <Link :href="entree.url">
                        <span
                            class="bg-champ text-isga-gris flex size-8 shrink-0 items-center justify-center rounded-md border">
                            <component :is="icones[entree.icone] ?? FileText" class="size-4"/>
                        </span>

                        <span class="grid min-w-0 flex-1 leading-tight">
                            <span class="truncate text-sm font-medium">{{ entree.titre }}</span>
                            <span class="text-muted-foreground truncate text-xs">{{
                                    entree.type
                                }} · {{ depuis(entree.visite_le) }}</span>
                        </span>
                    </Link>
                </DropdownMenuItem>
            </div>

            <p v-if="!etat_historique.entrees.length" class="text-muted-foreground px-2 py-6 text-center text-sm">
                Les pages que vous ouvrez apparaîtront ici.
            </p>

            <template v-else>
                <DropdownMenuSeparator/>

                <DropdownMenuItem class="text-muted-foreground cursor-pointer px-2 text-xs"
                                  @select="effacer_historique">
                    <Trash2 class="size-3.5"/>
                    Effacer l'historique
                </DropdownMenuItem>
            </template>
        </DropdownMenuContent>
    </DropdownMenu>
</template>


<script setup lang="ts">
import {ref, type Component} from 'vue';
import {Link} from '@inertiajs/vue3';
import {
    BarChart3,
    BookOpen,
    CalendarCheck,
    CalendarDays,
    ClipboardCheck,
    FileCheck,
    FileText,
    GraduationCap,
    History,
    Layers,
    LayoutDashboard,
    ListTree,
    Presentation,
    Repeat,
    ScrollText,
    Shapes,
    Trash2,
    UserCog,
    UserRound,
    UsersRound,
} from '@lucide/vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger
} from '@/components/ui/dropdown-menu';
import {effacer_historique, etat_historique} from './historique';

//==============================================================================================================
// Une icône par type de page (la liste et la fiche d'une même entité partagent la sienne)
//==============================================================================================================
const icones: Record<string, Component> = {
    'Année universitaire': CalendarDays,
    Cycle: Layers,
    Filière: Shapes,
    "Niveau d'études": ListTree,
    Groupe: UsersRound,
    Module: BookOpen,
    Enseignant: Presentation,
    Étudiant: GraduationCap,
    Séance: CalendarCheck,
    Appel: ClipboardCheck,
    Justificatif: FileCheck,
    Utilisateur: UserCog,
    Journal: ScrollText,
    'Mon compte': UserRound,
    'Tableau de bord': LayoutDashboard,
    Statistiques: BarChart3,
    "Passage d'année": Repeat,
};

//==============================================================================================================
// "à l'instant", "il y a 5 min", "il y a 2 h", "hier", "il y a 3 jours" (recalculé à chaque ouverture)
//==============================================================================================================
const maintenant = ref(Date.now());

const format_relatif = new Intl.RelativeTimeFormat('fr', {numeric: 'auto'});

const depuis = (horodatage: number) => {
    const minutes = Math.round((maintenant.value - horodatage) / 60000);

    if (minutes < 1) return "à l'instant";
    if (minutes < 60) return format_relatif.format(-minutes, 'minute');

    const heures = Math.round(minutes / 60);

    if (heures < 24) return format_relatif.format(-heures, 'hour');

    return format_relatif.format(-Math.round(heures / 24), 'day');
};
</script>
