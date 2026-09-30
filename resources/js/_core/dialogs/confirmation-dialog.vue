<template>
    <Dialog :open="etat.ouvert" @update:open="(ouvert) => !ouvert && repondre_confirmation(false)">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ etat.titre }}</DialogTitle>
                <DialogDescription v-if="etat.message">{{ etat.message }}</DialogDescription>
            </DialogHeader>

            <!--=================================================================================================-->
            <!-- Champ facultatif (ex. motif du refus) -->
            <!--=================================================================================================-->
            <div v-if="etat.champ" class="grid gap-2">
                <label for="confirmation-champ" class="text-sm font-medium">
                    {{ etat.champ.label }}
                    <span v-if="etat.champ.obligatoire" class="text-destructive">*</span>
                </label>

                <textarea
                    id="confirmation-champ"
                    v-model="etat.valeur"
                    v-focus
                    rows="3"
                    maxlength="500"
                    :placeholder="etat.champ.placeholder"
                    class="border-input bg-background focus-visible:ring-ring/50 rounded-md border px-3 py-2 text-sm outline-none focus-visible:ring-[3px]"
                />
            </div>

            <DialogFooter class="gap-2">
                <Button variant="outline" @click="repondre_confirmation(false)">Annuler</Button>

                <Button
                    :variant="etat.variante === 'destructive' ? 'destructive' : 'default'"
                    :disabled="champ_manquant"
                    @click="repondre_confirmation(true)"
                >
                    {{ etat.bouton ?? 'Confirmer' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>


<script setup lang="ts">
import {computed} from 'vue';
import {Button} from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle
} from '@/components/ui/dialog';
import {etat_confirmation as etat, repondre_confirmation} from './confirmation';

//==============================================================================================================
// Champ obligatoire vide : le bouton de confirmation reste désactivé
//==============================================================================================================
const champ_manquant = computed(() => Boolean(etat.champ?.obligatoire) && !etat.valeur.trim());
</script>
