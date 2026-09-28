import type { ModeVue } from '@/_core/renders';

export interface ChampProps {
    mode_vue: ModeVue;
    nom_champ: string;
    label?: string;
    error?: string;
    required?: boolean;
    placeholder?: string;
}
