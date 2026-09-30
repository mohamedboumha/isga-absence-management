<?php

use App\_Core\Middleware\RoleMiddleware;
use App\Features\User\UserController;
use App\Features\User\UserService;
use Illuminate\Support\Facades\Route;

//======================================================================================================================
// Gestion des comptes : super-administrateur uniquement (BF-03)
//======================================================================================================================
Route::controller(UserController::class)
     ->middleware(RoleMiddleware::roles([UserService::role_super_admin]))
     ->where(['cle' => '[A-Za-z0-9]{32}', 'mode_detail' => 'edit'])
     ->group(function () {
         Route::get('utilisateurs', 'list')
              ->name('utilisateurs.list');
         Route::post('utilisateur/{cle}/lien-mot-de-passe', 'lien_mot_de_passe')
              ->name('utilisateur.lien-mot-de-passe');
         Route::get('utilisateur/{cle?}/{mode_detail?}', 'detail')
              ->name('utilisateur.detail');
         Route::post('utilisateur/{cle?}', 'update')
              ->name('utilisateur.update');
     });
