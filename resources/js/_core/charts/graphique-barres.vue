<template>
    <div class="relative h-72">
        <Bar :data="donnees" :options="options" />
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import {
    BarElement,
    CategoryScale,
    Chart as ChartJS,
    Legend,
    LinearScale,
    Tooltip,
} from 'chart.js';
import { Bar } from 'vue-chartjs';

ChartJS.register(BarElement, CategoryScale, LinearScale, Tooltip, Legend);

interface GraphiqueBarresInterface {
    labels: string[];
    valeurs: number[];
    libelle: string;
    suffixe?: string;
    couleur?: string;
}

const props = withDefaults(defineProps<GraphiqueBarresInterface>(), {
    suffixe: '',
    couleur: '#2563eb',
});

const donnees = computed(() => ({
    labels: props.labels,
    datasets: [
        {
            label: props.libelle,
            data: props.valeurs,
            backgroundColor: props.couleur,
            borderRadius: 4,
        },
    ],
}));

const options = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            callbacks: {
                label: (contexte: any) =>
                    `${contexte.parsed.y}${props.suffixe}`,
            },
        },
    },
    scales: { y: { beginAtZero: true } },
}));
</script>
