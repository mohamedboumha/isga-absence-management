<template>
    <div v-if="donnees.heures.length" class="overflow-x-auto">
        <table class="w-full border-separate border-spacing-1 text-xs">
            <thead>
            <tr>
                <th class="w-14"/>
                <th v-for="jour in donnees.jours" :key="jour" scope="col"
                    class="text-muted-foreground pb-1 font-medium">{{ jour }}
                </th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="(heure, index_heure) in donnees.heures" :key="heure">
                <th scope="row" class="text-muted-foreground pr-2 text-right font-medium tabular-nums">{{ heure }}</th>

                <td v-for="(taux, index_jour) in donnees.cellules[index_heure]" :key="index_jour">
                        <span
                            class="flex h-10 min-w-12 items-center justify-center rounded-md font-medium tabular-nums"
                            :class="taux === null ? 'bg-muted/50 text-muted-foreground' : ''"
                            :style="taux === null ? undefined : style_cellule(taux)"
                            :title="taux === null ? `${donnees.jours[index_jour]} ${heure} : aucune séance` : `${donnees.jours[index_jour]} ${heure} : ${formater_nombre(taux)} % d'absence`"
                        >
                            {{ taux === null ? '—' : `${formater_nombre(taux)} %` }}
                        </span>
                </td>
            </tr>
            </tbody>
        </table>

        <p class="text-muted-foreground mt-3 text-xs">Plus la case est foncée, plus le taux d'absence du créneau est
            élevé.</p>
    </div>

    <p v-else class="text-muted-foreground py-8 text-center text-sm">Aucune séance tenue sur cette période.</p>
</template>


<script setup lang="ts">
import {formater_nombre} from '@/_core/detail/taux';

export interface DonneesChaleur {
    jours: string[];
    heures: string[];
    cellules: (number | null)[][];
    max: number;
}

interface CarteChaleurInterface {
    donnees: DonneesChaleur;
}

const props = defineProps<CarteChaleurInterface>();

//==============================================================================================================
// Intensité proportionnelle au créneau le plus absent ; texte blanc sur les cases foncées
//==============================================================================================================
const style_cellule = (taux: number) => {
    const intensite = props.donnees.max > 0 ? Math.round(8 + (taux / props.donnees.max) * 82) : 8;

    return {
        backgroundColor: `color-mix(in oklab, var(--absent) ${intensite}%, var(--champ-fond))`,
        color: intensite > 55 ? '#FFFFFF' : 'var(--foreground)',
    };
};
</script>
