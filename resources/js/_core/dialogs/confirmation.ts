import {reactive} from 'vue';

export interface DemandeConfirmation {
    titre: string;
    message?: string;
    bouton?: string;
    variante?: 'default' | 'destructive';
    champ?: {
        label: string;
        placeholder?: string;
        obligatoire?: boolean;
    };
}

export interface ReponseConfirmation {
    confirme: boolean;
    valeur: string;
}

interface EtatConfirmation extends DemandeConfirmation {
    ouvert: boolean;
    valeur: string;
    resoudre: ((reponse: ReponseConfirmation) => void) | null;
}

//==============================================================================================================
// État partagé : un seul dialogue, monté une fois dans AppLayout
//==============================================================================================================
export const etat_confirmation = reactive<EtatConfirmation>({
    ouvert: false,
    titre: '',
    valeur: '',
    resoudre: null,
});

//==============================================================================================================
// Ouvre le dialogue ; la promesse se résout quand l'utilisateur répond
//==============================================================================================================
export function demander_confirmation(demande: DemandeConfirmation): Promise<ReponseConfirmation> {
    return new Promise((resoudre) => {
        Object.assign(etat_confirmation, {
            message: undefined,
            bouton: undefined,
            variante: 'default',
            champ: undefined,
            ...demande,
            ouvert: true,
            valeur: '',
            resoudre,
        });
    });
}

export function repondre_confirmation(confirme: boolean): void {
    const resoudre = etat_confirmation.resoudre;

    etat_confirmation.ouvert = false;
    etat_confirmation.resoudre = null;

    resoudre?.({confirme, valeur: etat_confirmation.valeur.trim()});
}
