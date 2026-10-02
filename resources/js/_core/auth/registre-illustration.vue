<template>
    <figure class="flex flex-col gap-5" aria-hidden="true">
        <!--=====================================================================================================-->
        <!-- Le registre : une ligne par étudiant, une colonne par séance -->
        <!--=====================================================================================================-->
        <div class="bg-background w-fit rounded-xl border p-5">
            <!-- En-tête : les séances -->
            <div class="mb-3 flex items-center gap-1.5 pl-[6.5rem]">
                <span v-for="colonne in nb_colonnes" :key="colonne" class="bg-foreground/10 h-1.5 w-5 rounded-full"/>
            </div>

            <!-- Lignes : les étudiants -->
            <div v-for="(ligne, index_ligne) in registre" :key="index_ligne" class="flex items-center gap-1.5 py-[3px]">
                <span class="mr-2 flex w-24 items-center gap-2">
                    <span class="bg-foreground/15 size-4 shrink-0 rounded-full"/>
                    <span class="bg-foreground/15 h-2 rounded-full" :style="{ width: `${ligne.largeur_nom}%` }"/>
                </span>

                <span
                    v-for="(statut, index_colonne) in ligne.cases"
                    :key="index_colonne"
                    class="case size-5 rounded-[5px]"
                    :class="classes[statut]"
                    :style="{ animationDelay: `${index_colonne * 70 + 150}ms` }"
                />
            </div>
        </div>

        <!--=====================================================================================================-->
        <!-- Légende -->
        <!--=====================================================================================================-->
        <figcaption class="text-muted-foreground flex gap-5 text-xs">
            <span class="flex items-center gap-1.5"><span class="bg-present/25 size-3 rounded-[3px]"/>Présent</span>
            <span class="flex items-center gap-1.5"><span class="bg-absent size-3 rounded-[3px]"/>Absent</span>
            <span class="flex items-center gap-1.5"><span class="bg-justifie/70 size-3 rounded-[3px]"/>Absence justifiée</span>
        </figcaption>
    </figure>
</template>


<script setup lang="ts">
type StatutCase = 'present' | 'absent' | 'justifie' | 'a_venir';

const nb_lignes = 8;
const nb_colonnes = 11;
const nb_colonnes_a_venir = 2;

//==============================================================================================================
// Générateur pseudo-aléatoire à graine fixe : le même registre à chaque affichage
//==============================================================================================================
let graine = 7;

const aleatoire = () => {
    graine = (graine * 16807) % 2147483647;

    return graine / 2147483647;
};

const registre = Array.from({length: nb_lignes}, () => ({
    largeur_nom: 45 + Math.round(aleatoire() * 50),
    cases: Array.from({length: nb_colonnes}, (_, colonne): StatutCase => {
        if (colonne >= nb_colonnes - nb_colonnes_a_venir) return 'a_venir';

        const tirage = aleatoire();

        if (tirage < 0.8) return 'present';
        if (tirage < 0.91) return 'absent';

        return 'justifie';
    }),
}));

const classes: Record<StatutCase, string> = {
    present: 'bg-present/25',
    absent: 'bg-absent',
    justifie: 'bg-justifie/70',
    a_venir: 'border border-dashed border-foreground/20',
};
</script>


<style scoped>
/*==========================================================================================================*/
/* Un seul mouvement : les séances se remplissent colonne par colonne, comme un appel                        */
/*==========================================================================================================*/
.case {
    animation: remplir 420ms ease-out both;
}

@keyframes remplir {
    from {
        opacity: 0;
        transform: scale(0.6);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

@media (prefers-reduced-motion: reduce) {
    .case {
        animation: none;
    }
}
</style>
