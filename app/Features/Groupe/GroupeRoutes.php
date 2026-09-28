<?php

use App\_Core\Middleware\RoleMiddleware;
use App\Features\Groupe\GroupeController;
use App\Features\User\UserService;
use Illuminate\Support\Facades\Route;

Route::controller(GroupeController::class)
     ->middleware(RoleMiddleware::roles(UserService::roles_administration))
     ->where(['cle' => '[A-Za-z0-9]{32}', 'mode_detail' => 'edit'])
     ->group(function () {
         Route::get('groupes', 'list')
              ->name('groupes.list');
         Route::get('groupe/{cle?}/{mode_detail?}', 'detail')
              ->name('groupe.detail');
         Route::post('groupe/{cle?}', 'update')
              ->name('groupe.update');
         Route::delete('groupe/{cle}', 'delete')
              ->name('groupe.delete');
     });
