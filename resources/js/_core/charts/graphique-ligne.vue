<template>
    <div class="relative h-72">
        <Line :data="donnees" :options="options" />
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import {
    CategoryScale,
    Chart as ChartJS,
    Filler,
    LinearScale,
    LineElement,
    PointElement,
    Tooltip,
} from 'chart.js';
import { Line } from 'vue-chartjs';

ChartJS.register(
    LineElement,
    PointElement,
    CategoryScale,
    LinearScale,
    Tooltip,
    Filler,
);

interface GraphiqueLigneInterface {
    labels: string[];
    valeurs: number[];
    libelle: string;
    couleur?: string;
}

const props = withDefaults(defineProps<GraphiqueLigneInterface>(), {
    couleur: '#dc2626',
});

const donnees = computed(() => ({
    labels: props.labels,
    datasets: [
        {
            label: props.libelle,
            data: props.valeurs,
            borderColor: props.couleur,
            backgroundColor: `${props.couleur}22`,
            fill: true,
            tension: 0.3,
            pointRadius: 2,
        },
    ],
}));

const options = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
};
</script>
