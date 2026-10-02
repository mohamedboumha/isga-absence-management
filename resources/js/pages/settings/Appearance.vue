<template>
    <Head :title="contenu.titre"/>

    <main class="bg-background flex min-h-svh flex-col items-center justify-center p-6">
        <div class="flex w-full max-w-md flex-col items-center gap-6 text-center">
            <img src="/images/logo-isga.png" alt="ISGA" class="h-11 w-auto"/>

            <div class="bg-card flex w-full flex-col items-center gap-4 rounded-xl border p-8">
                <span class="text-isga-gris text-6xl font-semibold tabular-nums">{{ statut }}</span>

                <div class="flex flex-col gap-2">
                    <h1 class="text-xl font-semibold">{{ contenu.titre }}</h1>
                    <p class="text-muted-foreground text-sm">{{ message || contenu.message }}</p>
                </div>

                <div class="mt-2 flex flex-wrap justify-center gap-2">
                    <Button as-child>
                        <Link :href="est_connecte ? '/dashboard' : '/login'">
                            {{ est_connecte ? 'Retour au tableau de bord' : 'Se connecter' }}
                        </Link>
                    </Button>

                    <Button variant="outline" @click="revenir">Page précédente</Button>
                </div>
            </div>
        </div>
    </main>
</template>


<script setup lang="ts">
import {computed} from 'vue';
import {Head, Link, usePage} from '@inertiajs/vue3';
import {Button} from '@/components/ui/button';

interface ErreurInterface {
    statut: number;
    message?: string | null;
}

const props = defineProps<ErreurInterface>();

const contenus: Record<number, { titre: string; message: string }> = {
    403: {titre: 'Accès refusé', message: "Vous n'avez pas les droits nécessaires pour ouvrir cette page."},
    404: {titre: 'Page introuvable', message: "Cette page n'existe pas ou l'élément a été supprimé."},
    500: {
        titre: 'Erreur du serveur',
        message: "Un problème est survenu de notre côté. Réessayez dans un instant ; s'il persiste, prévenez l'administration."
    },
    503: {
        titre: 'Maintenance en cours',
        message: "L'application est momentanément indisponible. Réessayez dans quelques minutes."
    },
};

const contenu = computed(() => contenus[props.statut] ?? contenus[500]);

const page = usePage();
const est_connecte = computed(() => Boolean(page.props.auth?.user));

const revenir = () => window.history.back();
</script>
