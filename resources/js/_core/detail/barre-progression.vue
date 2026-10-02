<template>
    <div
        class="bg-champ h-2 w-full overflow-hidden rounded-full"
        role="progressbar"
        :aria-valuenow="pourcentage"
        aria-valuemin="0"
        aria-valuemax="100"
        :aria-label="label"
    >
        <div class="h-full rounded-full transition-[width] duration-300" :class="classe_couleur"
             :style="{ width: `${Math.min(Math.max(pourcentage, 0), 100)}%` }"/>
    </div>
</template>


<script setup lang="ts">
import {computed} from 'vue';

interface BarreProgressionInterface {
    pourcentage: number;
    label?: string;
}

const props = withDefaults(defineProps<BarreProgressionInterface>(), {
    label: 'Avancement',
});

//==============================================================================================================
// Terminé : vert ; en cours : gris ISGA
//==============================================================================================================
const classe_couleur = computed(() => (props.pourcentage >= 100 ? 'bg-present' : 'bg-isga-gris'));
</script>
