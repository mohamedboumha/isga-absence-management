<template>
    <CarteSection titre="Calendrier de présence" :sous_titre="`Année ${calendrier.annee}, un carré par jour`">
        <div class="overflow-x-auto pb-1">
            <div class="inline-flex gap-[3px]" role="img"
                 :aria-label="`Calendrier de présence de l'année ${calendrier.annee}`">
                <!-- Jours de la semaine -->
                <div class="text-muted-foreground mr-1.5 flex flex-col gap-[3px] text-[10px]">
                    <span class="h-4"/>
                    <span v-for="jour in calendrier.jours_semaine" :key="jour" class="flex h-3.5 items-center">{{
                            jour
                        }}</span>
                </div>

                <!-- Une colonne par semaine -->
                <div v-for="(semaine, index) in calendrier.semaines" :key="index" class="flex flex-col gap-[3px]">
                    <span class="text-muted-foreground h-4 overflow-visible text-[10px] whitespace-nowrap">{{
                            semaine.mois ?? ''
                        }}</span>

                    <span
                        v-for="(jour, index_jour) in semaine.jours"
                        :key="index_jour"
                        class="size-3.5 rounded-[3px]"
                        :class="classes_case[jour.statut]"
                        :title="jour.libelle ?? undefined"
                    />
                </div>
            </div>
        </div>

        <!-- Légende -->
        <div class="text-muted-foreground mt-4 flex flex-wrap gap-x-5 gap-y-2 text-xs">
            <span v-for="statut in legende" :key="statut" class="flex items-center gap-1.5">
                <span class="size-3 rounded-[3px]" :class="classes_case[statut]"/>
                {{ libelles_case[statut] }}
            </span>
        </div>
    </CarteSection>
</template>


<script setup lang="ts">
import CarteSection from './carte-section.vue';
import {classes_case, libelles_case, type CalendrierPresence, type StatutCase} from './registre';

interface CalendrierPresenceInterface {
    calendrier: CalendrierPresence;
}

const props = defineProps<CalendrierPresenceInterface>();

const legende: StatutCase[] = ['present', 'absent', 'justifie', 'a_faire', 'a_venir', 'vide'];
</script>
