import {reactive} from 'vue';
import type {Page} from '@inertiajs/core';
import {router} from '@inertiajs/vue3';

export interface EntreeHistorique {
    chemin: string;
    url: string;
    titre: string;
    type: string;
    icone: string;
    visite_le: number;
}

interface TypeFiche {
    label: string;
    prefixes: string[];
}

const nb_max_entrees = 15;

//==============================================================================================================
// Titre de la liste (premier élément du fil d'Ariane) => type de la fiche, et préfixes à retirer du titre
// ex. "Groupes" + "Groupe 1AP-A"  =>  type "Groupe", titre "1AP-A"
//==============================================================================================================
const types_fiches: Record<string, TypeFiche> = {
    'Années universitaires': {label: 'Année universitaire', prefixes: ['Année universitaire ']},
    Cycles: {label: 'Cycle', prefixes: []},
    Filières: {label: 'Filière', prefixes: ['Filière ']},
    "Niveaux d'études": {label: "Niveau d'études", prefixes: ['Niveau ']},
    Groupes: {label: 'Groupe', prefixes: ['Groupe ']},
    Modules: {label: 'Module', prefixes: ['Module ']},
    Enseignants: {label: 'Enseignant', prefixes: []},
    Étudiants: {label: 'Étudiant', prefixes: []},
    Séances: {label: 'Séance', prefixes: ['Séance ']},
    'Mes séances': {label: 'Appel', prefixes: ['Appel ', 'Appel — ']},
    Justificatifs: {label: 'Justificatif', prefixes: ['Justificatif — ']},
    Utilisateurs: {label: 'Utilisateur', prefixes: []},
    Journal: {label: 'Journal', prefixes: []},
};

//==============================================================================================================
// Pages sans fil d'Ariane utilisable : nom du composant Inertia => titre
//==============================================================================================================
const titres_pages: Record<string, string> = {
    dashboard: 'Tableau de bord',
    Dashboard: 'Tableau de bord',
    'settings/Profile': 'Profil',
    'settings/Security': 'Mot de passe',
    'settings/Appearance': 'Apparence',
};

const get_type = (titre_liste: string): TypeFiche =>
    types_fiches[titre_liste] ?? {label: titre_liste.replace(/s$/, ''), prefixes: []};

const retirer_prefixe = (titre: string, prefixes: string[]) => {
    const prefixe = prefixes.find((candidat) => titre.startsWith(candidat));

    return prefixe ? titre.slice(prefixe.length) : titre;
};

//==============================================================================================================
// Une page => son titre, son type et son icône
//==============================================================================================================
const decrire = (page: Page): Pick<EntreeHistorique, 'titre' | 'type' | 'icone'> | null => {
    const props = page.props as Record<string, any>;
    const breadcrumbs = (props.breadcrumbs as { title: string; href: string }[] | undefined) ?? [];
    const titre_page = props.titre_page as string | undefined;

    //==========================================================================================================
    // Mon compte : Profil, Mot de passe, Apparence
    //==========================================================================================================
    if (page.component.startsWith('settings/')) {
        return {titre: titres_pages[page.component] ?? 'Mon compte', type: 'Mon compte', icone: 'Mon compte'};
    }

    //==========================================================================================================
    // Fiche (consultation, création, modification) : "Liste > Fiche"
    //==========================================================================================================
    if (breadcrumbs.length >= 2 && props.mode_vue && props.mode_vue !== 'list') {
        const type = get_type(breadcrumbs[0].title);
        const mode = props.mode_vue === 'create' ? ' · création' : props.mode_vue === 'edit' ? ' · modification' : '';

        return {
            titre: retirer_prefixe(breadcrumbs[breadcrumbs.length - 1].title, type.prefixes),
            type: `${type.label}${mode}`,
            icone: type.label,
        };
    }

    //==========================================================================================================
    // Liste : "Étudiants"
    //==========================================================================================================
    if (props.mode_vue === 'list') {
        const titre = titre_page ?? breadcrumbs[0]?.title ?? page.component;

        return {titre, type: 'Liste', icone: get_type(titre).label};
    }

    //==========================================================================================================
    // Autres pages : tableau de bord, statistiques, passage d'année...
    //==========================================================================================================
    const titre = titres_pages[page.component] ?? titre_page ?? breadcrumbs[breadcrumbs.length - 1]?.title;

    return titre ? {titre, type: 'Page', icone: titre} : null;
};

//==============================================================================================================
// État partagé : l'historique de l'utilisateur connecté (un par utilisateur, dans ce navigateur)
//==============================================================================================================
export const etat_historique = reactive<{ cle_stockage: string | null; entrees: EntreeHistorique[] }>({
    cle_stockage: null,
    entrees: [],
});

const lire = (cle: string): EntreeHistorique[] => {
    try {
        const entrees = JSON.parse(localStorage.getItem(cle) ?? '[]');

        return Array.isArray(entrees) ? entrees.filter((entree) => entree?.chemin) : [];
    } catch {
        return [];
    }
};

const ecrire = () => {
    if (!etat_historique.cle_stockage) return;

    try {
        localStorage.setItem(etat_historique.cle_stockage, JSON.stringify(etat_historique.entrees));
    } catch {
        // Stockage plein ou désactivé : l'historique reste en mémoire pour cette session
    }
};

//==============================================================================================================
// À chaque page : une entrée par chemin (sans paramètres) ; elle garde la dernière adresse complète,
// pour retrouver une liste avec sa recherche, ses filtres et sa page
//==============================================================================================================
const enregistrer = (page: Page) => {
    const props = page.props as Record<string, any>;
    const user_id = props.auth?.user?.id;

    if (!user_id || page.component === 'erreur') {
        if (!user_id) {
            etat_historique.cle_stockage = null;
            etat_historique.entrees = [];
        }

        return;
    }

    const cle = `isga-historique-${user_id}`;

    if (etat_historique.cle_stockage !== cle) {
        etat_historique.cle_stockage = cle;
        etat_historique.entrees = lire(cle);
    }

    const description = decrire(page);

    if (!description) return;

    const chemin = page.url.split('?')[0];

    etat_historique.entrees = [
        {chemin, url: page.url, ...description, visite_le: Date.now()},
        ...etat_historique.entrees.filter((autre) => autre.chemin !== chemin),
    ].slice(0, nb_max_entrees);

    ecrire();
};

export function initialiser_historique(): void {
    router.on('navigate', (event) => enregistrer(event.detail.page));
}

export function effacer_historique(): void {
    etat_historique.entrees = [];
    ecrire();
}
