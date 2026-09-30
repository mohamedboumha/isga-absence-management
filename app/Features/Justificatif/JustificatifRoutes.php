<?php

use App\_Core\Middleware\RoleMiddleware;
use App\Features\Justificatif\JustificatifController;
use App\Features\User\UserService;
use Illuminate\Support\Facades\Route;

Route::controller(JustificatifController::class)
     ->middleware(RoleMiddleware::roles(UserService::roles_administration))
     ->where(['cle' => '[A-Za-z0-9]{32}', 'mode_detail' => 'edit'])
     ->group(function () {
         Route::get('justificatifs', 'list')
              ->name('justificatifs.list');
         Route::get('justificatif/{cle}/fichier', 'fichier')
              ->name('justificatif.fichier');
         Route::get('justificatif/{cle?}/{mode_detail?}', 'detail')
              ->name('justificatif.detail');
         Route::post('justificatif/{cle}/valider', 'valider')
              ->name('justificatif.valider');
         Route::post('justificatif/{cle}/refuser', 'refuser')
              ->name('justificatif.refuser');
         Route::post('justificatif/{cle?}', 'update')
              ->name('justificatif.update');
         Route::delete('justificatif/{cle}', 'delete')
              ->name('justificatif.delete');
     });
