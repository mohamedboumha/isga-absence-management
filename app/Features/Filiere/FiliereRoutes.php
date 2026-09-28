<?php

use App\_Core\Middleware\RoleMiddleware;
use App\Features\Filiere\FiliereController;
use App\Features\User\UserService;
use Illuminate\Support\Facades\Route;

Route::controller(FiliereController::class)
     ->middleware(RoleMiddleware::roles(UserService::roles_administration))
     ->where(['cle' => '[A-Za-z0-9]{32}', 'mode_detail' => 'edit'])
     ->group(function () {
         Route::get('filieres', 'list')
              ->name('filieres.list');
         Route::get('filiere/{cle?}/{mode_detail?}', 'detail')
              ->name('filiere.detail');
         Route::post('filiere/{cle?}', 'update')
              ->name('filiere.update');
         Route::delete('filiere/{cle}', 'delete')
              ->name('filiere.delete');
     });
