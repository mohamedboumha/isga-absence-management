<template>
    <span
        class="inline-flex items-center gap-1.5 rounded-md px-2 py-0.5 text-xs font-semibold whitespace-nowrap"
        :style="{
            color: couleur,
            backgroundColor: `color-mix(in oklab, ${couleur} 12%, transparent)`,
            boxShadow: `inset 0 0 0 1px color-mix(in oklab, ${couleur} 30%, transparent)`,
        }"
    >
        <span class="size-1.5 shrink-0 rounded-full" :style="{ backgroundColor: couleur }" aria-hidden="true"/>
        {{ label }}
    </span>
</template>


<script setup lang="ts">
interface BadgeCouleurInterface {
    couleur: string;
    label: string;
}

const props = defineProps<BadgeCouleurInterface>();
</script>
