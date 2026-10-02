<template>
    <Button type="button" variant="outline" @click="ouvrir">
        <FileText class="size-4"/>
        {{ label }}
    </Button>
</template>


<script setup lang="ts">
import {FileText} from '@lucide/vue';
import {Button} from '@/components/ui/button';
import {ouvrir_document} from './visionneuse';

interface BoutonDocumentInterface {
    url: string;
    titre: string;
    label: string;
    nom_fichier?: string;
}

const props = defineProps<BoutonDocumentInterface>();

const ouvrir = () =>
    ouvrir_document({
        titre: props.titre,
        url: props.url,
        type: 'pdf',
        nom_fichier: props.nom_fichier,
    });
</script>
