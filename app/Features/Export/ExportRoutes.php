<?php

use App\_Core\Middleware\RoleMiddleware;
use App\Features\Export\ExportController;
use App\Features\User\UserService;
use Illuminate\Support\Facades\Route;

//======================================================================================================================
// Relevés et rapports : administration
//======================================================================================================================
Route::controller(ExportController::class)
     ->middleware(RoleMiddleware::roles(UserService::roles_administration))
     ->where(['cle' => '[A-Za-z0-9]{32}'])
     ->group(function () {
         Route::get('etudiant/{cle}/releve', 'releve_etudiant')
              ->name('export.releve-etudiant');
         Route::get('groupe/{cle}/rapport', 'rapport_groupe')
              ->name('export.rapport-groupe');
     });

//======================================================================================================================
// Feuille de présence : administration et enseignant de la séance (vérifié dans le controller)
//======================================================================================================================
Route::get('seance/{cle}/feuille-presence', [ExportController::class, 'feuille_presence'])
     ->middleware(RoleMiddleware::roles(UserService::roles_tous))
     ->where(['cle' => '[A-Za-z0-9]{32}'])
     ->name('export.feuille-presence');
