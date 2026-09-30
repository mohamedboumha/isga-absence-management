<?php

use App\_Core\Middleware\RoleMiddleware;
use App\Features\Statistique\StatistiqueController;
use App\Features\User\UserService;
use Illuminate\Support\Facades\Route;

Route::controller(StatistiqueController::class)
     ->middleware(RoleMiddleware::roles(UserService::roles_administration))
     ->group(function () {
         Route::get('statistiques', 'index')
              ->name('statistiques.index');
         Route::get('statistiques/export', 'export')
              ->name('statistiques.export');
     });
