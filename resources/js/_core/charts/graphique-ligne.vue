<template>
    <div class="relative h-72">
        <Line :data="donnees" :options="options"/>
    </div>
</template>


<script setup lang="ts">
import {computed} from 'vue';
import {CategoryScale, Chart as ChartJS, Filler, LinearScale, LineElement, PointElement, Tooltip} from 'chart.js';
import {Line} from 'vue-chartjs';

ChartJS.register(LineElement, PointElement, CategoryScale, LinearScale, Tooltip, Filler);

interface GraphiqueLigneInterface {
    labels: string[];
    valeurs: number[];
    libelle: string;
    suffixe?: string;
    couleur?: string;
}

const props = withDefaults(defineProps<GraphiqueLigneInterface>(), {
    suffixe: '',
    couleur: '#5E5E5D',
});

const donnees = computed(() => ({
    labels: props.labels,
    datasets: [
        {
            label: props.libelle,
            data: props.valeurs,
            borderColor: props.couleur,
            backgroundColor: `${props.couleur}1F`,
            borderWidth: 2,
            fill: true,
            tension: 0.3,
            pointRadius: 0,
            pointHoverRadius: 4,
            pointBackgroundColor: props.couleur,
        },
    ],
}));

const options = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    interaction: {mode: 'index' as const, intersect: false},
    plugins: {
        legend: {display: false},
        tooltip: {
            callbacks: {
                label: (contexte: { parsed: { y: number } }) =>
                    `${props.libelle} : ${contexte.parsed.y.toLocaleString('fr-FR', {maximumFractionDigits: 1})}${props.suffixe}`,
            },
        },
    },
    scales: {
        x: {grid: {display: false}, ticks: {maxTicksLimit: 10, color: '#8A8A88'}},
        y: {
            beginAtZero: true,
            grid: {color: 'rgba(127, 127, 127, 0.12)'},
            ticks: {precision: 0, color: '#8A8A88', callback: (valeur: string | number) => `${valeur}${props.suffixe}`},
        },
    },
}));
</script>
