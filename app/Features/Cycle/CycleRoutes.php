<?php

use App\_Core\Middleware\RoleMiddleware;
use App\Features\Cycle\CycleController;
use App\Features\User\UserService;
use Illuminate\Support\Facades\Route;

Route::controller(CycleController::class)
     ->middleware(RoleMiddleware::roles(UserService::roles_administration))
     ->where(['cle' => '[A-Za-z0-9]{32}', 'mode_detail' => 'edit'])
     ->group(function () {
         Route::get('cycles', 'list')
              ->name('cycles.list');
         Route::get('cycle/{cle?}/{mode_detail?}', 'detail')
              ->name('cycle.detail');
         Route::post('cycle/{cle?}', 'update')
              ->name('cycle.update');
         Route::delete('cycle/{cle}', 'delete')
              ->name('cycle.delete');
     });
