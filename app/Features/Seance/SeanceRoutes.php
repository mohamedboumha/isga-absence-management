<?php

use App\_Core\Middleware\RoleMiddleware;
use App\Features\Seance\SeanceController;
use App\Features\User\UserService;
use Illuminate\Support\Facades\Route;

Route::controller(SeanceController::class)
     ->middleware(RoleMiddleware::roles(UserService::roles_administration))
     ->where(['cle' => '[A-Za-z0-9]{32}', 'mode_detail' => 'edit'])
     ->group(function () {
         Route::get('seances', 'list')
              ->name('seances.list');
         Route::get('seance/{cle?}/{mode_detail?}', 'detail')
              ->name('seance.detail');
         Route::post('seance/{cle?}', 'update')
              ->name('seance.update');
         Route::delete('seance/{cle}', 'delete')
              ->name('seance.delete');
     });
