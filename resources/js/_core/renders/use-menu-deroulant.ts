import {onBeforeUnmount, onMounted, ref, type Ref} from 'vue';

//==============================================================================================================
// Ouverture / fermeture d'un panneau déroulant ; se ferme au clic en dehors du conteneur
//==============================================================================================================
export function useMenuDeroulant(conteneur: Ref<HTMLElement | null>) {
    const ouvert = ref(false);

    const ouvrir = () => {
        ouvert.value = true;
    };

    const fermer = () => {
        ouvert.value = false;
    };

    const basculer = () => {
        ouvert.value = !ouvert.value;
    };

    //==========================================================================================================
    // "Clic en dehors" : on regarde le chemin du clic au moment où il a eu lieu (composedPath).
    // Le chemin reste valable même si l'élément cliqué disparaît entre-temps (ex. une option d'une liste
    // ouverte DANS ce panneau, qui se referme aussitôt choisie).
    //==========================================================================================================
    const sur_clic_document = (event: MouseEvent) => {
        if (!ouvert.value || !conteneur.value) return;

        if (!event.composedPath().includes(conteneur.value)) {
            fermer();
        }
    };

    onMounted(() => document.addEventListener('mousedown', sur_clic_document));
    onBeforeUnmount(() => document.removeEventListener('mousedown', sur_clic_document));

    return {ouvert, ouvrir, fermer, basculer};
}
