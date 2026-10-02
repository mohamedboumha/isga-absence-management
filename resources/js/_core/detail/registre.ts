//==============================================================================================================
// Statuts d'une case (mêmes clés que GroupeRegistreService et EtudiantCalendrierService)
//==============================================================================================================
export type StatutCase = 'present' | 'absent' | 'justifie' | 'a_faire' | 'a_venir' | 'annulee' | 'vide' | 'hors';

export const classes_case: Record<StatutCase, string> = {
    present: 'bg-present/30',
    absent: 'bg-absent',
    justifie: 'bg-justifie/75',
    a_faire: 'border border-dashed border-attente bg-attente/10',
    a_venir: 'border border-dashed border-foreground/20',
    annulee: 'bg-muted',
    vide: 'bg-muted/70',
    hors: 'invisible',
};

export const libelles_case: Record<StatutCase, string> = {
    present: 'Présent',
    absent: 'Absent',
    justifie: 'Absence justifiée',
    a_faire: 'Appel à faire',
    a_venir: 'À venir',
    annulee: 'Séance annulée',
    vide: 'Pas de séance',
    hors: '',
};

//==============================================================================================================
// Registre d'un groupe (un mois)
//==============================================================================================================
export interface ColonneRegistre {
    cle: string;
    jour: string;
    date: string;
    horaire: string;
    module: string;
    intitule: string;
    couleur: string;
    annulee: boolean;
    url: string;
}

export interface LigneRegistre {
    nom_complet: string;
    cne: string;
    url: string;
    cases: StatutCase[];
    nb_absences: number;
}

export interface RegistreGroupe {
    mois: string;
    mois_label: string;
    mois_precedent: string | null;
    mois_suivant: string | null;
    module: number | null;
    modules: { valeur: number; label: string }[];
    colonnes: ColonneRegistre[];
    lignes: LigneRegistre[];
}

//==============================================================================================================
// Calendrier de présence d'un étudiant (une année)
//==============================================================================================================
export interface JourCalendrier {
    statut: StatutCase;
    libelle: string | null;
}

export interface CalendrierPresence {
    annee: string;
    jours_semaine: string[];
    semaines: { mois: string | null; jours: JourCalendrier[] }[];
}
