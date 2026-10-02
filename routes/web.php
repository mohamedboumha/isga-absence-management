<?php

use Illuminate\Support\Facades\Route;

//======================================================================================================================
// Pas de page d'accueil publique : la connexion, ou le tableau de bord si on est déjà connecté
//======================================================================================================================
Route::get('/', fn() => redirect()->route(auth()->check() ? 'dashboard' : 'login'))->name('home');

Route::middleware(['auth'])->group(function () {
    foreach (glob(app_path('Features/*/*Routes.php')) ?: [] as $routes) {
        require $routes;
    }
});

require __DIR__ . '/settings.php';
