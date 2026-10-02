<template>
    <Dialog :open="etat.ouvert" @update:open="(ouvert) => !ouvert && fermer_document()">
        <DialogContent class="flex max-h-[96vh] w-fit max-w-[96vw] flex-col gap-0 overflow-hidden p-0 sm:max-w-[96vw]">
            <!--=================================================================================================-->
            <!-- En-tête : titre + actions -->
            <!--=================================================================================================-->
            <div class="flex items-center justify-between gap-6 border-b px-5 py-3 pr-14">
                <div class="min-w-0">
                    <DialogTitle class="truncate">{{ etat.document?.titre }}</DialogTitle>
                    <DialogDescription v-if="etat.document?.nom_fichier" class="truncate">{{
                            etat.document.nom_fichier
                        }}
                    </DialogDescription>
                </div>

                <div v-if="etat.document" class="flex shrink-0 gap-2">
                    <Button as-child variant="outline" size="sm">
                        <a :href="etat.document.url" target="_blank" rel="noopener">
                            <ExternalLink class="size-4"/>
                            Ouvrir dans un onglet
                        </a>
                    </Button>

                    <Button as-child size="sm">
                        <a :href="url_telechargement" :download="etat.document.nom_fichier ?? ''">
                            <Download class="size-4"/>
                            Télécharger
                        </a>
                    </Button>
                </div>
            </div>

            <!--=================================================================================================-->
            <!-- Le document, à sa taille (la fenêtre s'adapte au document) -->
            <!--=================================================================================================-->
            <div class="bg-champ flex justify-center">
                <PagesDocument v-if="etat.document" :key="etat.document.url" :url="etat.document.url"
                               :type="etat.document.type"/>
            </div>
        </DialogContent>
    </Dialog>
</template>


<script setup lang="ts">
import {computed} from 'vue';
import {Download, ExternalLink} from '@lucide/vue';
import {Button} from '@/components/ui/button';
import {Dialog, DialogContent, DialogDescription, DialogTitle} from '@/components/ui/dialog';
import PagesDocument from './pages-document.vue';
import {etat_visionneuse as etat, fermer_document, get_url_telechargement} from './visionneuse';

//==============================================================================================================
// Fichier local (blob:) : téléchargé tel quel ; fichier du serveur : "?telecharger=1"
//==============================================================================================================
const url_telechargement = computed(() => {
    const document = etat.document;

    if (!document) return '';
    if (document.url_telechargement) return document.url_telechargement;

    return document.url.startsWith('blob:') ? document.url : get_url_telechargement(document.url);
});
</script>
