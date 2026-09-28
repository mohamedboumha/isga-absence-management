<?php

use App\_Core\Middleware\RoleMiddleware;
use App\Features\AnneeUniversitaire\AnneeUniversitaireController;
use App\Features\User\UserService;
use Illuminate\Support\Facades\Route;

Route::controller(AnneeUniversitaireController::class)
     ->middleware(RoleMiddleware::roles(UserService::roles_administration))
     ->where(['cle' => '[A-Za-z0-9]{32}', 'mode_detail' => 'edit'])
     ->group(function () {
         Route::get('annees-universitaires', 'list')
              ->name('annees-universitaires.list');
         Route::get('annee-universitaire/{cle?}/{mode_detail?}', 'detail')
              ->name('annee-universitaire.detail');
         Route::post('annee-universitaire/{cle?}', 'update')
              ->name('annee-universitaire.update');
         Route::delete('annee-universitaire/{cle}', 'delete')
              ->name('annee-universitaire.delete');
     });
