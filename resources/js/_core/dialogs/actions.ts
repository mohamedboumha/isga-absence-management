import {router} from '@inertiajs/vue3';
import {toast} from 'vue-sonner';
import {demander_confirmation} from './confirmation';

//==============================================================================================================
// Erreur renvoyée par le serveur (ex. suppression refusée) : notification rouge avec le premier message
//==============================================================================================================
export function afficher_erreurs(errors: Record<string, string>): void {
    const message = Object.values(errors)[0];

    if (message) {
        toast.error(message);
    }
}

//==============================================================================================================
// Suppression avec confirmation : "Supprimer" en rouge, erreurs éventuelles en notification
//==============================================================================================================
export async function supprimer_avec_confirmation(url: string, titre: string, message: string): Promise<void> {
    const {confirme} = await demander_confirmation({
        titre,
        message,
        bouton: 'Supprimer',
        variante: 'destructive',
    });

    if (!confirme) {
        return;
    }

    router.delete(url, {onError: afficher_erreurs});
}
