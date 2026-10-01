export type Tonalite = 'present' | 'absent' | 'attente' | 'justifie' | 'neutre';

export interface StatutDefinition {
    tonalite: Tonalite;
    label: string;
}

//==============================================================================================================
// Un statut = une tonalité (couleur porteuse de sens) + un libellé, identiques partout dans l'application
//==============================================================================================================
export const statuts: Record<string, StatutDefinition> = {
    // Appel
    present: { tonalite: 'present', label: 'Présent' },
    absent: { tonalite: 'absent', label: 'Absent' },
    justifie: { tonalite: 'justifie', label: 'Absence justifiée' },

    // Justificatifs
    EN_ATTENTE: { tonalite: 'attente', label: 'En attente' },
    VALIDE: { tonalite: 'present', label: 'Validé' },
    REFUSE: { tonalite: 'absent', label: 'Refusé' },
    hors_delai: { tonalite: 'attente', label: 'Hors délai' },

    // Séances
    planifiee: { tonalite: 'neutre', label: 'Planifiée' },
    annulee: { tonalite: 'neutre', label: 'Annulée' },
    appel_fait: { tonalite: 'present', label: 'Appel fait' },
    appel_a_faire: { tonalite: 'attente', label: 'Appel à faire' },

    // Comptes et années
    actif: { tonalite: 'present', label: 'Actif' },
    desactive: { tonalite: 'neutre', label: 'Désactivé' },
    en_cours: { tonalite: 'present', label: 'En cours' },

    // Décisions de fin d'année
    ADMIS: { tonalite: 'present', label: 'Admis' },
    REDOUBLANT: { tonalite: 'attente', label: 'Redoublant' },
    DIPLOME: { tonalite: 'justifie', label: 'Diplômé' },
    SORTANT: { tonalite: 'neutre', label: 'Sortant' },

    // Passage d'année d'un groupe
    traite: { tonalite: 'present', label: 'Traité' },
    a_traiter: { tonalite: 'attente', label: 'À traiter' },
};
