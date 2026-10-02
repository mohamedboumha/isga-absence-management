import type {Tonalite} from '@/_core/renders/statuts';

//==============================================================================================================
// Taux d'absence : vert sous 5 %, orange à partir de 5 %, rouge à partir de 10 %
//==============================================================================================================
export const tonalite_taux = (taux: number): Tonalite => {
    if (taux >= 10) return 'absent';
    if (taux >= 5) return 'attente';

    return 'present';
};

//==============================================================================================================
// 7.4  =>  "7,4"
//==============================================================================================================
export const formater_nombre = (nombre: number): string => nombre.toLocaleString('fr-FR', {maximumFractionDigits: 1});
