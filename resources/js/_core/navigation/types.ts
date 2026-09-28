export interface SideBarItem {
    label: string;
    icon: string;
    href: string;
}

export interface SideBarGroupe {
    label: string | null;
    items: SideBarItem[];
}
