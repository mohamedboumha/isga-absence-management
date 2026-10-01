import {
    BookOpen,
    CalendarCheck,
    CalendarRange,
    ChartColumn,
    Circle,
    FileCheck,
    GraduationCap,
    History,
    Layers,
    LayoutGrid,
    ListTree,
    Presentation,
    ShieldUser,
    TrendingUp,
    UserRound,
    Users,
    UserX,
} from '@lucide/vue';
import type {Component} from 'vue';

//==============================================================================================================
// Nom envoyé par SideBarService (PHP)  =>  composant icône
// Ajouter ici chaque nouvelle icône utilisée dans le menu
//==============================================================================================================
const icons: Record<string, Component> = {
    BookOpen,
    CalendarCheck,
    CalendarRange,
    ChartColumn,
    FileCheck,
    GraduationCap,
    History,
    Layers,
    LayoutGrid,
    ListTree,
    Presentation,
    ShieldUser,
    TrendingUp,
    UserRound,
    Users,
    UserX,
};

export const get_icon = (nom: string): Component => icons[nom] ?? Circle;
