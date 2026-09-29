<?php

use App\_Core\Middleware\RoleMiddleware;
use App\Features\Appel\AppelController;
use App\Features\User\UserService;
use Illuminate\Support\Facades\Route;

//======================================================================================================================
// Appel : administration et enseignants (le controller vérifie que l'enseignant est celui de la séance)
//======================================================================================================================
Route::controller(AppelController::class)
     ->middleware(RoleMiddleware::roles(UserService::roles_tous))
     ->where(['cle' => '[A-Za-z0-9]{32}'])
     ->group(function () {
         Route::get('seance/{cle}/appel', 'detail')
              ->name('appel.detail');
         Route::post('seance/{cle}/appel', 'update')
              ->name('appel.update');
     });

//======================================================================================================================
// Mes séances : enseignants uniquement
//======================================================================================================================
Route::get('mes-seances', [AppelController::class, 'mes_seances'])
     ->middleware(RoleMiddleware::roles([UserService::role_enseignant]))
     ->name('mes-seances.list');
