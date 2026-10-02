//==============================================================================================================
// Une séance résumée, telle que l'envoient les services de consultation
//==============================================================================================================
export interface SeanceResume {
    cle: string;
    date: string;
    horaire: string;
    type: string;
    module: string;
    intitule: string;
    couleur: string;
    groupe?: string;
    enseignant?: string;
    statut: string;
    url: string;
}
