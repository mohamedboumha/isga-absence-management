import {
    BookOpen,
    CalendarCheck,
    CalendarDays,
    CalendarRange,
    ChartColumn,
    Circle,
    FileCheck,
    GraduationCap,
    LayoutGrid,
    Presentation,
    ShieldUser,
    UserRound,
    Users,
    UserX,
} from '@lucide/vue';
import type { Component } from 'vue';

const icons: Record<string, Component> = {
    BookOpen,
    CalendarCheck,
    CalendarDays,
    CalendarRange,
    ChartColumn,
    FileCheck,
    GraduationCap,
    LayoutGrid,
    Presentation,
    ShieldUser,
    UserRound,
    Users,
    UserX,
};

export const get_icon = (nom: string): Component => icons[nom] ?? Circle;
