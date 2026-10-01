<?php

use App\_Core\Middleware\RoleMiddleware;
use App\Features\NiveauEtude\NiveauEtudeController;
use App\Features\User\UserService;
use Illuminate\Support\Facades\Route;

Route::controller(NiveauEtudeController::class)
     ->middleware(RoleMiddleware::roles(UserService::roles_administration))
     ->where(['cle' => '[A-Za-z0-9]{32}', 'mode_detail' => 'edit'])
     ->group(function () {
         Route::get('niveaux-etudes', 'list')->name('niveaux-etudes.list');
         Route::get('niveau-etude/{cle?}/{mode_detail?}', 'detail')->name('niveau-etude.detail');
         Route::post('niveau-etude/{cle?}', 'update')->name('niveau-etude.update');
         Route::delete('niveau-etude/{cle}', 'delete')->name('niveau-etude.delete');
     });
