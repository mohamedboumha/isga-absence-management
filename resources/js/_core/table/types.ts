export interface TableHeader {
    label: string;
    nom_colonne: string;
    render: 'chaine' | 'date' | 'boolean';
    triable: boolean;
}

export interface TableItem {
    cle: string;
    valeurs: Record<string, any>;
    url: string | null;
}

export interface Table {
    headers: TableHeader[];
    items: TableItem[];
    search: string | null;
    tri_par: string | null;
    tri_direction: 'asc' | 'desc';
    pagination: {
        page: number;
        last_page: number;
        total: number;
        per_page: number;
    };
}
