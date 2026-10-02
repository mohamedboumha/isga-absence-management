import * as pdfjs from 'pdfjs-dist';
import type {PDFDocumentProxy} from 'pdfjs-dist';
import url_worker from 'pdfjs-dist/build/pdf.worker.min.mjs?url';

//==============================================================================================================
// pdf.js décode les PDF dans un "worker" (en arrière-plan, sans bloquer la page)
//==============================================================================================================
pdfjs.GlobalWorkerOptions.workerSrc = url_worker;

export interface Dimensions {
    largeur: number;
    hauteur: number;
}

//==============================================================================================================
// Un PDF n'est chargé qu'une fois, même s'il est affiché dans l'aperçu puis dans la visionneuse
//==============================================================================================================
const cache = new Map<string, Promise<PDFDocumentProxy>>();

export function charger_pdf(url: string): Promise<PDFDocumentProxy> {
    if (!cache.has(url)) {
        cache.set(url, pdfjs.getDocument({url}).promise);
    }

    return cache.get(url)!;
}

export function oublier_pdf(url: string): void {
    cache.delete(url);
}

//==============================================================================================================
// Dimensions réelles d'une page (en points PDF)
//==============================================================================================================
export async function get_dimensions_page(pdf: PDFDocumentProxy, numero: number): Promise<Dimensions> {
    const viewport = (await pdf.getPage(numero)).getViewport({scale: 1});

    return {largeur: viewport.width, hauteur: viewport.height};
}

//==============================================================================================================
// Taille qui tient dans la boîte (largeur max, hauteur max) en gardant les proportions du document
//==============================================================================================================
export function ajuster(dimensions: Dimensions, largeur_max: number, hauteur_max: number, agrandissement_max = 1.5): Dimensions {
    const echelle = Math.min(largeur_max / dimensions.largeur, hauteur_max / dimensions.hauteur, agrandissement_max);

    return {
        largeur: Math.floor(dimensions.largeur * echelle),
        hauteur: Math.floor(dimensions.hauteur * echelle),
    };
}

//==============================================================================================================
// Dessine une page dans un <canvas>, à la largeur voulue (net sur les écrans Retina)
//==============================================================================================================
export async function rendre_page(pdf: PDFDocumentProxy, numero: number, canvas: HTMLCanvasElement, largeur: number): Promise<void> {
    const page = await pdf.getPage(numero);
    const taille = page.getViewport({scale: 1});
    const densite = window.devicePixelRatio || 1;
    const viewport = page.getViewport({scale: (largeur / taille.width) * densite});
    const contexte = canvas.getContext('2d');

    if (!contexte) return;

    canvas.width = Math.floor(viewport.width);
    canvas.height = Math.floor(viewport.height);
    canvas.style.width = `${largeur}px`;
    canvas.style.height = `${viewport.height / densite}px`;

    await page.render({
        canvas,
        canvasContext: contexte,
        viewport
    } as unknown as Parameters<typeof page.render>[0]).promise;
}
