<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')
     ->name('home');

Route::middleware(['auth', 'verified'])
     ->group(function () {
         Route::inertia('dashboard', 'Dashboard')
              ->name('dashboard');
     });

Route::middleware(['auth'])
     ->group(function () {
         foreach (glob(app_path('Features/*/*Routes.php')) ?: [] as $routes) {
             require $routes;
         }
     });

require __DIR__ . '/settings.php';
