<template>
    <div class="grid gap-1.5">
        <!--=========================================================================================================-->
        <!-- Libellé (+ action facultative à droite, ex. "Mot de passe oublié ?") -->
        <!--=========================================================================================================-->
        <div v-if="label || $slots['label-action']" class="flex min-h-5 items-center justify-between gap-2">
            <label :for="nom_champ" class="text-sm font-medium">
                {{ label }}
                <span v-if="required" class="text-absent ml-0.5" aria-hidden="true">*</span>
            </label>

            <slot name="label-action"/>
        </div>

        <!--=========================================================================================================-->
        <!-- Le champ lui-même -->
        <!--=========================================================================================================-->
        <slot/>

        <InputError :message="error"/>
    </div>
</template>


<script setup lang="ts">
import InputError from '@/components/InputError.vue';

interface ChampConteneurInterface {
    nom_champ: string;
    label?: string;
    required?: boolean;
    error?: string;
}

const props = defineProps<ChampConteneurInterface>();
</script>
