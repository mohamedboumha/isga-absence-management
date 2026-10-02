<template>
    <CarteSection
        titre="Registre"
        :sous_titre="`${registre.lignes.length} étudiant(s), ${registre.colonnes.length} séance(s) ce mois-ci`"
        :avec_marges="false"
    >
        <!--=====================================================================================================-->
        <!-- Mois et module -->
        <!--=====================================================================================================-->
        <template #actions>
            <div class="flex flex-wrap items-center gap-2">
                <select
                    :value="registre.module ?? ''"
                    class="champ h-8 w-auto max-w-56 px-2 text-sm"
                    aria-label="Module affiché"
                    @change="recharger({ module: ($event.target as HTMLSelectElement).value || undefined })"
                >
                    <option value="">Tous les modules</option>
                    <option v-for="module in registre.modules" :key="module.valeur" :value="module.valeur">
                        {{ module.label }}
                    </option>
                </select>

                <div class="bg-card flex items-center rounded-md border">
                    <button
                        type="button"
                        class="hover:bg-muted flex size-8 items-center justify-center rounded-l-md disabled:pointer-events-none disabled:opacity-30"
                        aria-label="Mois précédent"
                        :disabled="!registre.mois_precedent"
                        @click="recharger({ mois: registre.mois_precedent })"
                    >
                        <ChevronLeft class="size-4"/>
                    </button>

                    <span class="min-w-32 px-2 text-center text-sm font-medium">{{ registre.mois_label }}</span>

                    <button
                        type="button"
                        class="hover:bg-muted flex size-8 items-center justify-center rounded-r-md disabled:pointer-events-none disabled:opacity-30"
                        aria-label="Mois suivant"
                        :disabled="!registre.mois_suivant"
                        @click="recharger({ mois: registre.mois_suivant })"
                    >
                        <ChevronRight class="size-4"/>
                    </button>
                </div>
            </div>
        </template>

        <!--=====================================================================================================-->
        <!-- La grille : étudiants × séances -->
        <!--=====================================================================================================-->
        <div v-if="registre.colonnes.length && registre.lignes.length" class="overflow-x-auto"
             :class="{ 'opacity-60': chargement }">
            <table class="border-separate border-spacing-0 text-sm">
                <thead>
                <tr>
                    <th scope="col"
                        class="bg-card sticky left-0 z-10 min-w-60 px-5 py-2 text-left align-bottom text-xs font-medium">
                        <span class="text-muted-foreground">Étudiant</span>
                    </th>

                    <th v-for="colonne in registre.colonnes" :key="colonne.cle" scope="col"
                        class="px-0.5 pt-2 pb-1.5 align-bottom font-normal">
                        <Link
                            :href="colonne.url"
                            class="hover:bg-muted flex flex-col items-center gap-1 rounded-md px-0.5 py-1"
                            :title="`${colonne.jour} ${colonne.date}, ${colonne.horaire}, ${colonne.module} — ${colonne.intitule}${colonne.annulee ? ' (annulée)' : ''}`"
                        >
                            <span class="text-muted-foreground text-[10px] leading-none">{{ colonne.jour }}</span>
                            <span class="text-[11px] leading-none font-medium tabular-nums"
                                  :class="{ 'text-muted-foreground line-through': colonne.annulee }">
                                    {{ colonne.date }}
                                </span>
                            <span class="h-1 w-5 rounded-full" :style="{ backgroundColor: colonne.couleur }"/>
                        </Link>
                    </th>

                    <th scope="col"
                        class="text-muted-foreground px-4 py-2 text-right align-bottom text-xs font-medium whitespace-nowrap">
                        Absences
                    </th>
                </tr>
                </thead>

                <tbody>
                <tr v-for="ligne in registre.lignes" :key="ligne.cne" class="group">
                    <td class="bg-card group-hover:bg-muted/40 sticky left-0 z-10 border-t px-5 py-1.5">
                        <Link :href="ligne.url" class="flex min-w-0 flex-col leading-tight">
                            <span class="truncate font-medium hover:underline">{{ ligne.nom_complet }}</span>
                            <span class="text-muted-foreground text-xs tabular-nums">{{ ligne.cne }}</span>
                        </Link>
                    </td>

                    <td v-for="(statut, index) in ligne.cases" :key="index"
                        class="group-hover:bg-muted/40 border-t px-0.5 py-1.5">
                            <span
                                class="mx-auto block size-6 rounded-[5px]"
                                :class="classes_case[statut]"
                                :title="`${ligne.nom_complet}, ${registre.colonnes[index].date} ${registre.colonnes[index].module} : ${libelles_case[statut]}`"
                                :aria-label="libelles_case[statut]"
                                role="img"
                            />
                    </td>

                    <td
                        class="group-hover:bg-muted/40 border-t px-4 text-right font-semibold tabular-nums"
                        :class="ligne.nb_absences ? 'text-absent' : 'text-muted-foreground'"
                    >
                        {{ ligne.nb_absences }}
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

        <p v-else class="text-muted-foreground px-5 py-10 text-center text-sm">
            {{
                registre.lignes.length ? `Aucune séance en ${registre.mois_label.toLowerCase()}.` : 'Aucun étudiant inscrit dans ce groupe.'
            }}
        </p>

        <!--=====================================================================================================-->
        <!-- Légende -->
        <!--=====================================================================================================-->
        <div class="text-muted-foreground flex flex-wrap gap-x-5 gap-y-2 border-t px-5 py-3 text-xs">
            <span v-for="statut in legende" :key="statut" class="flex items-center gap-1.5">
                <span class="size-3.5 rounded-[3px]" :class="classes_case[statut]"/>
                {{ libelles_case[statut] }}
            </span>
        </div>
    </CarteSection>
</template>


<script setup lang="ts">
import {ref} from 'vue';
import {Link, router} from '@inertiajs/vue3';
import {ChevronLeft, ChevronRight} from '@lucide/vue';
import CarteSection from './carte-section.vue';
import {classes_case, libelles_case, type RegistreGroupe, type StatutCase} from './registre';

interface RegistreGroupeInterface {
    registre: RegistreGroupe;
}

const props = defineProps<RegistreGroupeInterface>();

const legende: StatutCase[] = ['present', 'absent', 'justifie', 'a_faire', 'a_venir', 'annulee'];

//==============================================================================================================
// Changement de mois ou de module : on ne recharge que les données de la fiche (rechargement partiel)
//==============================================================================================================
const chargement = ref(false);

const recharger = (params: Record<string, unknown>) => {
    router.get(
        window.location.pathname,
        {
            mois: props.registre.mois,
            module: props.registre.module ?? undefined,
            ...params,
        },
        {
            only: ['consultation'],
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onStart: () => (chargement.value = true),
            onFinish: () => (chargement.value = false),
        },
    );
};
</script>
