<template>
    <section class="bg-card rounded-xl border">
        <!--=========================================================================================================-->
        <!-- En-tête teinté (couleur des champs) -->
        <!--=========================================================================================================-->
        <header class="bg-champ flex items-center justify-between gap-3 rounded-t-xl border-b px-5 py-3.5">
            <div class="min-w-0">
                <h2 class="font-semibold">
                    {{ titre }}
                    <span v-if="compteur !== undefined && compteur !== null"
                          class="text-muted-foreground ml-1 font-normal tabular-nums">({{ compteur }})</span>
                </h2>
                <p v-if="sous_titre" class="text-muted-foreground text-xs">{{ sous_titre }}</p>
            </div>

            <div class="flex shrink-0 items-center gap-3">
                <slot name="actions"/>

                <Link
                    v-if="lien"
                    :href="lien.url"
                    class="text-muted-foreground hover:text-foreground inline-flex items-center gap-1 text-sm font-medium"
                >
                    {{ lien.label }}
                    <ArrowRight class="size-4"/>
                </Link>
            </div>
        </header>

        <div :class="{ 'p-5': avec_marges }">
            <slot/>
        </div>
    </section>
</template>


<script setup lang="ts">
import {Link} from '@inertiajs/vue3';
import {ArrowRight} from '@lucide/vue';

interface CarteSectionInterface {
    titre: string;
    sous_titre?: string | null;
    compteur?: number | null;
    lien?: { url: string; label: string } | null;
    avec_marges?: boolean;
}

const props = withDefaults(defineProps<CarteSectionInterface>(), {
    sous_titre: null,
    compteur: undefined,
    lien: null,
    avec_marges: true,
});
</script>
