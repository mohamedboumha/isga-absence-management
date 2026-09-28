<?php

use App\_Core\Middleware\RoleMiddleware;
use App\Features\Semestre\SemestreController;
use App\Features\User\UserService;
use Illuminate\Support\Facades\Route;

Route::controller(SemestreController::class)
     ->middleware(RoleMiddleware::roles(UserService::roles_administration))
     ->where(['cle' => '[A-Za-z0-9]{32}', 'mode_detail' => 'edit'])
     ->group(function () {
         Route::get('semestres', 'list')
              ->name('semestres.list');
         Route::get('semestre/{cle?}/{mode_detail?}', 'detail')
              ->name('semestre.detail');
         Route::post('semestre/{cle?}', 'update')
              ->name('semestre.update');
         Route::delete('semestre/{cle}', 'delete')
              ->name('semestre.delete');
     });
