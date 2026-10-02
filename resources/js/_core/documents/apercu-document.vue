<template>
    <!--=========================================================================================================-->
    <!-- Le document à ses proportions réelles, dans une boîte de 144 × 204 px au plus -->
    <!--=========================================================================================================-->
    <div class="flex h-[204px] w-36 shrink-0 items-center justify-center">
        <!-- Image -->
        <img
            v-if="type === 'image'"
            :src="url"
            alt=""
            class="max-h-full max-w-full rounded-sm bg-white shadow-sm ring-1 ring-black/10"
        />

        <!-- PDF : première page -->
        <canvas v-else v-show="etat === 'pret'" ref="canvas"
                class="block rounded-sm bg-white shadow-sm ring-1 ring-black/10"/>

        <!-- Chargement / erreur -->
        <div v-if="type === 'pdf' && etat !== 'pret'"
             class="bg-champ text-muted-foreground flex h-full w-full items-center justify-center rounded-sm">
            <FileWarning v-if="etat === 'erreur'" class="size-6"/>
            <span v-else class="size-full animate-pulse"/>
        </div>
    </div>
</template>


<script setup lang="ts">
import {onMounted, ref, watch} from 'vue';
import {FileWarning} from '@lucide/vue';
import {ajuster, charger_pdf, get_dimensions_page, rendre_page} from './pdf';
import type {TypeDocument} from './visionneuse';

interface ApercuDocumentInterface {
    url: string;
    type: TypeDocument;
}

const props = defineProps<ApercuDocumentInterface>();

//==============================================================================================================
// Boîte de l'aperçu : 144 × 204 px au plus
//==============================================================================================================
const largeur_max = 144;
const hauteur_max = 204;

const canvas = ref<HTMLCanvasElement | null>(null);
const etat = ref<'chargement' | 'pret' | 'erreur'>('chargement');

const dessiner = async () => {
    if (props.type !== 'pdf' || !canvas.value) return;

    etat.value = 'chargement';

    try {
        const pdf = await charger_pdf(props.url);
        const taille = ajuster(await get_dimensions_page(pdf, 1), largeur_max, hauteur_max);

        await rendre_page(pdf, 1, canvas.value, taille.largeur);

        etat.value = 'pret';
    } catch {
        etat.value = 'erreur';
    }
};

onMounted(dessiner);
watch(() => props.url, dessiner);
</script>
