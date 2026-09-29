<?php

use App\_Core\Middleware\RoleMiddleware;
use App\Features\Enseignant\EnseignantController;
use App\Features\User\UserService;
use Illuminate\Support\Facades\Route;

Route::controller(EnseignantController::class)
     ->middleware(RoleMiddleware::roles(UserService::roles_administration))
     ->where(['cle' => '[A-Za-z0-9]{32}', 'mode_detail' => 'edit'])
     ->group(function () {
         Route::get('enseignants', 'list')
              ->name('enseignants.list');
         Route::get('enseignant/{cle?}/{mode_detail?}', 'detail')
              ->name('enseignant.detail');
         Route::post('enseignant/{cle?}', 'update')
              ->name('enseignant.update');
         Route::delete('enseignant/{cle}', 'delete')
              ->name('enseignant.delete');
     });
