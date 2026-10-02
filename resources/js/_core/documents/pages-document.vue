<template>
    <div class="flex flex-col items-center gap-3 p-5">
        <!--=========================================================================================================-->
        <!-- La page, ajustée à l'écran à ses proportions réelles -->
        <!--=========================================================================================================-->
        <div class="relative flex items-center justify-center"
             :style="taille ? { width: `${taille.largeur}px`, height: `${taille.hauteur}px` } : {}">
            <!-- Image -->
            <img
                v-if="type === 'image'"
                :src="url"
                alt=""
                class="block bg-white shadow-md ring-1 ring-black/10"
                :style="taille ? { width: `${taille.largeur}px`, height: `${taille.hauteur}px` } : { visibility: 'hidden' }"
                @load="sur_image_chargee"
            />

            <!-- PDF -->
            <canvas v-else v-show="etat === 'pret'" ref="canvas" class="block bg-white shadow-md ring-1 ring-black/10"/>

            <!-- Chargement / erreur -->
            <p v-if="etat === 'chargement' && type === 'pdf'" class="text-muted-foreground px-24 py-32 text-sm">
                Chargement du document...</p>
            <p v-if="etat === 'erreur'" class="text-absent px-12 py-24 text-sm">Le document n'a pas pu être affiché.
                Utilisez « Télécharger ».</p>
        </div>

        <!--=========================================================================================================-->
        <!-- Navigation entre les pages (← → au clavier) -->
        <!--=========================================================================================================-->
        <div v-if="nb_pages > 1" class="flex items-center gap-3">
            <Button type="button" variant="outline" size="icon" :disabled="page_courante <= 1"
                    aria-label="Page précédente" @click="aller_a(page_courante - 1)">
                <ChevronLeft class="size-4"/>
            </Button>

            <span class="text-muted-foreground min-w-28 text-center text-sm tabular-nums">Page {{ page_courante }} sur {{
                    nb_pages
                }}</span>

            <Button type="button" variant="outline" size="icon" :disabled="page_courante >= nb_pages"
                    aria-label="Page suivante" @click="aller_a(page_courante + 1)">
                <ChevronRight class="size-4"/>
            </Button>
        </div>
    </div>
</template>


<script setup lang="ts">
import {onBeforeUnmount, onMounted, ref} from 'vue';
import type {PDFDocumentProxy} from 'pdfjs-dist';
import {ChevronLeft, ChevronRight} from '@lucide/vue';
import {Button} from '@/components/ui/button';
import {ajuster, charger_pdf, get_dimensions_page, rendre_page, type Dimensions} from './pdf';
import type {TypeDocument} from './visionneuse';

interface PagesDocumentInterface {
    url: string;
    type: TypeDocument;
}

const props = defineProps<PagesDocumentInterface>();

const canvas = ref<HTMLCanvasElement | null>(null);
const taille = ref<Dimensions | null>(null);
const etat = ref<'chargement' | 'pret' | 'erreur'>('chargement');

const nb_pages = ref(0);
const page_courante = ref(1);

let pdf: PDFDocumentProxy | null = null;

//==============================================================================================================
// Place disponible : 92 % de l'écran, moins l'en-tête, les marges et la navigation
//==============================================================================================================
const get_place_disponible = () => ({
    largeur: window.innerWidth * 0.92 - 40,
    hauteur: window.innerHeight * 0.92 - 64 - 40 - (nb_pages.value > 1 ? 48 : 0),
});

//==============================================================================================================
// Image : taille naturelle, réduite si elle ne tient pas (jamais agrandie)
//==============================================================================================================
const sur_image_chargee = (event: Event) => {
    const image = event.target as HTMLImageElement;
    const place = get_place_disponible();

    taille.value = ajuster({
        largeur: image.naturalWidth,
        hauteur: image.naturalHeight
    }, place.largeur, place.hauteur, 1);
    etat.value = 'pret';
};

//==============================================================================================================
// PDF : une page à la fois, ajustée à l'écran
//==============================================================================================================
const afficher_page = async (numero: number) => {
    if (!pdf || !canvas.value) return;

    const place = get_place_disponible();

    taille.value = ajuster(await get_dimensions_page(pdf, numero), place.largeur, place.hauteur);

    await rendre_page(pdf, numero, canvas.value, taille.value.largeur);

    etat.value = 'pret';
};

const aller_a = (numero: number) => {
    if (numero < 1 || numero > nb_pages.value) return;

    page_courante.value = numero;
    afficher_page(numero);
};

const sur_touche = (event: KeyboardEvent) => {
    if (event.key === 'ArrowLeft') aller_a(page_courante.value - 1);
    if (event.key === 'ArrowRight') aller_a(page_courante.value + 1);
};

onMounted(async () => {
    window.addEventListener('keydown', sur_touche);

    if (props.type !== 'pdf') return;

    try {
        pdf = await charger_pdf(props.url);
        nb_pages.value = pdf.numPages;

        await afficher_page(1);
    } catch {
        etat.value = 'erreur';
    }
});

onBeforeUnmount(() => window.removeEventListener('keydown', sur_touche));
</script>
