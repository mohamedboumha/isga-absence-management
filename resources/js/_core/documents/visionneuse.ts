import {reactive} from 'vue';

export type TypeDocument = 'pdf' | 'image';

export interface DocumentAffiche {
    titre: string;
    url: string;
    type: TypeDocument;
    nom_fichier?: string;
    url_telechargement?: string;
}

//==============================================================================================================
// État partagé : une seule visionneuse, montée une fois dans AppLayout
//==============================================================================================================
export const etat_visionneuse = reactive<{ ouvert: boolean; document: DocumentAffiche | null }>({
    ouvert: false,
    document: null,
});

export function ouvrir_document(document: DocumentAffiche): void {
    etat_visionneuse.document = document;
    etat_visionneuse.ouvert = true;
}

export function fermer_document(): void {
    etat_visionneuse.ouvert = false;
}

//==============================================================================================================
// "certificat.jpg" => image ; "releve.pdf" => pdf (le type MIME l'emporte quand on le connaît)
//==============================================================================================================
export function get_type_document(nom_fichier?: string | null, type_mime?: string): TypeDocument {
    if (type_mime) {
        return type_mime.startsWith('image/') ? 'image' : 'pdf';
    }

    return /\.(png|jpe?g|gif|webp)$/i.test(nom_fichier ?? '') ? 'image' : 'pdf';
}

//==============================================================================================================
// Même URL + "telecharger=1" : le serveur renvoie le fichier en téléchargement
//==============================================================================================================
export function get_url_telechargement(url: string): string {
    return `${url}${url.includes('?') ? '&' : '?'}telecharger=1`;
}
