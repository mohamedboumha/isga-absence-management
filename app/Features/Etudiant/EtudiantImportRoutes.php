<?php

use App\_Core\Middleware\RoleMiddleware;
use App\Features\Etudiant\EtudiantImportController;
use App\Features\User\UserService;
use Illuminate\Support\Facades\Route;

//======================================================================================================================
// Import d'étudiants (Excel) : administration uniquement
//======================================================================================================================
Route::controller(EtudiantImportController::class)
     ->middleware(RoleMiddleware::roles(UserService::roles_administration))
     ->group(function () {
         Route::get('etudiants/import', 'page')
              ->name('etudiants.import');
         Route::get('etudiants/import/modele', 'modele')
              ->name('etudiants.import.modele');
         Route::post('etudiants/import/analyser', 'analyser')
              ->name('etudiants.import.analyser');
         Route::post('etudiants/import/confirmer', 'confirmer')
              ->name('etudiants.import.confirmer');
         Route::post('etudiants/import/annuler', 'annuler')
              ->name('etudiants.import.annuler');
     });
