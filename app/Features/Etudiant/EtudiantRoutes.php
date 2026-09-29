<?php

use App\_Core\Middleware\RoleMiddleware;
use App\Features\Etudiant\EtudiantController;
use App\Features\User\UserService;
use Illuminate\Support\Facades\Route;

Route::controller(EtudiantController::class)
     ->middleware(RoleMiddleware::roles(UserService::roles_administration))
     ->where(['cle' => '[A-Za-z0-9]{32}', 'mode_detail' => 'edit'])
     ->group(function () {
         Route::get('etudiants', 'list')
              ->name('etudiants.list');
         Route::get('etudiant/{cle?}/{mode_detail?}', 'detail')
              ->name('etudiant.detail');
         Route::post('etudiant/{cle?}', 'update')
              ->name('etudiant.update');
         Route::delete('etudiant/{cle}', 'delete')
              ->name('etudiant.delete');
     });
