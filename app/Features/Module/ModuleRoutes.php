<?php

use App\_Core\Middleware\RoleMiddleware;
use App\Features\Module\ModuleController;
use App\Features\User\UserService;
use Illuminate\Support\Facades\Route;

Route::controller(ModuleController::class)
     ->middleware(RoleMiddleware::roles(UserService::roles_administration))
     ->where(['cle' => '[A-Za-z0-9]{32}', 'mode_detail' => 'edit'])
     ->group(function () {
         Route::get('modules', 'list')
              ->name('modules.list');
         Route::get('module/{cle?}/{mode_detail?}', 'detail')
              ->name('module.detail');
         Route::post('module/{cle?}', 'update')
              ->name('module.update');
         Route::delete('module/{cle}', 'delete')
              ->name('module.delete');
     });
