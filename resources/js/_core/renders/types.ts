import type { ModeVue } from '@/_core/renders';

export interface ChampProps {
    mode_vue: ModeVue;
    nom_champ: string;
    label?: string;
    error?: string;
    required?: boolean;
    placeholder?: string;
}

export interface SelectOption {
    valeur: string | number;
    label: string;
}
