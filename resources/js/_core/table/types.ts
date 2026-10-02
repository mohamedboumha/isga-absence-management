import type {SelectOption} from '@/_core/renders/types';

export type RenderTable = 'chaine' | 'date' | 'boolean' | 'nombre' | 'badge' | 'statut' | 'personne';

export type TypeFiltre = 'select' | 'periode' | 'oui_non';

export interface ValeurPeriode {
    du: string | null;
    au: string | null;
}

export interface TableHeader {
    label: string;
    nom_colonne: string;
    render: RenderTable;
    triable: boolean;
}

export interface TableFiltre {
    nom: string;
    label: string;
    type: TypeFiltre;
    options: SelectOption[];
    valeur: string | ValeurPeriode | null;
}

export interface TableActions {
    label: string | null;
    url_consulter: string;
    url_modifier: string | null;
    url_supprimer: string | null;
}

export interface TableItem {
    cle: string;
    valeurs: Record<string, any>;
    url: string | null;
    actions: TableActions | null;
}

export interface TablePagination {
    page: number;
    last_page: number;
    total: number;
    per_page: number;
    options: number[];
}

export interface Table {
    headers: TableHeader[];
    items: TableItem[];
    filtres: TableFiltre[];
    search: string | null;
    tri_par: string | null;
    tri_direction: 'asc' | 'desc';
    pagination: TablePagination;
}
