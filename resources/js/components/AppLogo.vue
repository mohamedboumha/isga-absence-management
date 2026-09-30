<template>
    <!--=========================================================================================================-->
    <!-- Barre latérale ouverte : logo ISGA + nom de l'application -->
    <!--=========================================================================================================-->
    <div class="flex min-w-0 items-center gap-3 group-data-[collapsible=icon]:hidden">
        <img src="/images/logo-isga.png" alt="ISGA — Institut supérieur d'ingénierie et des affaires" class="h-9 w-auto shrink-0" />

        <span class="text-muted-foreground border-l pl-3 text-xs leading-tight">
            Gestion<br />des absences
        </span>
    </div>

    <!--=========================================================================================================-->
    <!-- Barre latérale réduite : le "S" rouge du logo -->
    <!--=========================================================================================================-->
    <div
        class="bg-isga-rouge hidden size-8 items-center justify-center rounded-md text-sm font-bold text-white group-data-[collapsible=icon]:flex"
        aria-label="ISGA"
    >
        S
    </div>
</template>
