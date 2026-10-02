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

//==============================================================================================================
// Un groupe résumé (badge + effectif)
//==============================================================================================================
export interface GroupeResume {
    nom: string;
    effectif: number;
    couleur: string;
    url: string;
}

//==============================================================================================================
// Un niveau résumé, avec ses groupes de l'année (NiveauEtudeResumeService)
//==============================================================================================================
export interface NiveauResume {
    code: string;
    libelle: string;
    couleur: string;
    annee_cycle: number;
    nb_groupes: number;
    effectif: number;
    groupes: GroupeResume[];
    url: string;
}

//==============================================================================================================
// Une année du cycle et ses niveaux
//==============================================================================================================
export interface AnneeStructure {
    annee: number;
    label: string;
    niveaux: NiveauResume[];
}
