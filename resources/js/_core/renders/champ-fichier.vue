<template>
    <!--=========================================================================================================-->
    <!-- Mode list -->
    <!--=========================================================================================================-->
    <span v-if="mode_vue === renders.mode_list">{{ nom_fichier || '—' }}</span>

    <!--=========================================================================================================-->
    <!-- Modes detail -->
    <!--=========================================================================================================-->
    <ChampConteneur v-else :nom_champ="nom_champ" :label="label" :required="required && is_editable" :error="error">
        <!-- Saisie : champ cliquable qui ouvre le choix de fichier -->
        <label v-if="is_editable" :for="nom_champ" class="champ" :aria-invalid="Boolean(error)">
            <span class="truncate" :class="{ 'champ-vide': !fichier_choisi }">
                {{ fichier_choisi?.name ?? (url_fichier ? 'Remplacer le document...' : 'Choisir un fichier...') }}
            </span>
            <Upload class="size-4 shrink-0 opacity-60"/>
            <input :id="nom_champ" ref="champ_fichier" :name="nom_champ" type="file" :accept="accept" class="sr-only"
                   @change="choisir"/>
        </label>

        <!-- Consultation : le même champ, désactivé -->
        <div v-else class="champ" aria-disabled="true">
            <span class="truncate" :class="{ 'champ-vide': !nom_fichier }">{{ nom_fichier || '—' }}</span>
            <FileText class="size-4 shrink-0 opacity-60"/>
        </div>

        <p v-if="is_editable && aide" class="text-muted-foreground text-xs">{{ aide }}</p>

        <!--=====================================================================================================-->
        <!-- Aperçu : le document à ses proportions, puis son nom en dessous ; un clic l'ouvre -->
        <!--=====================================================================================================-->
        <div v-if="apercu" class="mt-1 flex w-36 flex-col gap-2">
            <button
                type="button"
                class="group focus-visible:ring-isga-gris relative rounded-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                :aria-label="`Ouvrir ${apercu.nom}`"
                @click="voir"
            >
                <ApercuDocument :key="apercu.url" :url="apercu.url" :type="apercu.type"/>

                <span
                    class="absolute inset-0 flex items-center justify-center rounded-sm text-white opacity-0 transition group-hover:bg-black/40 group-hover:opacity-100">
                    <Eye class="size-5"/>
                </span>
            </button>

            <div class="flex min-w-0 flex-col gap-0.5">
                <p class="truncate text-sm font-medium" :title="apercu.nom">{{ apercu.nom }}</p>
                <p class="text-muted-foreground text-xs">
                    {{ apercu.type === 'image' ? 'Image' : 'Document PDF' }}
                    <template v-if="apercu.taille">, {{ apercu.taille }}</template>
                </p>
            </div>

            <Button v-if="is_editable && fichier_choisi" type="button" variant="ghost" size="sm"
                    class="-ml-2 self-start" @click="retirer">
                Retirer
            </Button>
        </div>
    </ChampConteneur>
</template>


<script setup lang="ts">
import {computed, onBeforeUnmount, ref} from 'vue';
import {Eye, FileText, Upload} from '@lucide/vue';
import {Button} from '@/components/ui/button';
import ApercuDocument from '@/_core/documents/apercu-document.vue';
import {oublier_pdf} from '@/_core/documents/pdf';
import {get_type_document, ouvrir_document, type TypeDocument} from '@/_core/documents/visionneuse';
import {renders} from '@/_core/renders';
import ChampConteneur from './champ-conteneur.vue';
import type {ChampProps} from './types';

interface ChampFichierInterface extends ChampProps {
    accept?: string;
    aide?: string;
    url_fichier?: string | null;
    nom_fichier?: string | null;
}

interface Apercu {
    url: string;
    nom: string;
    type: TypeDocument;
    taille: string | null;
}

const props = defineProps<ChampFichierInterface>();
const valeur = defineModel<File | null>('valeur');

const is_editable = computed(() => props.mode_vue === renders.mode_create || props.mode_vue === renders.mode_edit);

//==============================================================================================================
// Fichier choisi : une URL locale (blob:) permet de l'afficher avant l'envoi
//==============================================================================================================
const champ_fichier = ref<HTMLInputElement | null>(null);
const fichier_choisi = ref<File | null>(null);
const url_locale = ref<string | null>(null);

const liberer_url_locale = () => {
    if (url_locale.value) {
        oublier_pdf(url_locale.value);
        URL.revokeObjectURL(url_locale.value);
        url_locale.value = null;
    }
};

const choisir = (event: Event) => {
    liberer_url_locale();

    fichier_choisi.value = (event.target as HTMLInputElement).files?.[0] ?? null;
    url_locale.value = fichier_choisi.value ? URL.createObjectURL(fichier_choisi.value) : null;
    valeur.value = fichier_choisi.value;
};

const retirer = () => {
    liberer_url_locale();

    fichier_choisi.value = null;
    valeur.value = null;

    if (champ_fichier.value) {
        champ_fichier.value.value = '';
    }
};

onBeforeUnmount(liberer_url_locale);

//==============================================================================================================
// "245 Ko", "1,2 Mo"
//==============================================================================================================
const formater_taille = (octets: number) =>
    octets < 1024 * 1024
        ? `${Math.max(1, Math.round(octets / 1024))} Ko`
        : `${(octets / 1024 / 1024).toLocaleString('fr-FR', {maximumFractionDigits: 1})} Mo`;

const apercu = computed<Apercu | null>(() => {
    if (fichier_choisi.value && url_locale.value) {
        return {
            url: url_locale.value,
            nom: fichier_choisi.value.name,
            type: get_type_document(fichier_choisi.value.name, fichier_choisi.value.type),
            taille: formater_taille(fichier_choisi.value.size),
        };
    }

    if (props.url_fichier) {
        return {
            url: props.url_fichier,
            nom: props.nom_fichier || 'Document',
            type: get_type_document(props.nom_fichier),
            taille: null,
        };
    }

    return null;
});

const voir = () => {
    if (!apercu.value) return;

    ouvrir_document({
        titre: props.label ?? 'Document',
        url: apercu.value.url,
        type: apercu.value.type,
        nom_fichier: apercu.value.nom,
    });
};
</script>
