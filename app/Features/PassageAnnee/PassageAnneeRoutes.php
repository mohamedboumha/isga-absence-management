<?php

use App\_Core\Middleware\RoleMiddleware;
use App\Features\PassageAnnee\PassageAnneeController;
use App\Features\User\UserService;
use Illuminate\Support\Facades\Route;

Route::controller(PassageAnneeController::class)
     ->middleware(RoleMiddleware::roles(UserService::roles_administration))
     ->group(function () {
         Route::get('passage-annee', 'index')
              ->name('passage-annee.index');
         Route::post('passage-annee', 'executer')
              ->name('passage-annee.executer');
     });
