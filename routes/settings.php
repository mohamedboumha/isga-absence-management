<?php

use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use Illuminate\Support\Facades\Route;

//======================================================================================================================
// Mon compte : profil en lecture seule (les comptes sont gérés par l'administration, RG-14),
// mot de passe et apparence
//======================================================================================================================
Route::middleware(['auth'])
     ->group(function () {
         Route::redirect('settings', '/settings/profile');

         Route::get('settings/profile', [ProfileController::class, 'edit'])
              ->name('profile.edit');

         Route::get('settings/security', [SecurityController::class, 'edit'])
              ->name('security.edit');

         Route::put('settings/password', [SecurityController::class, 'update'])
              ->middleware('throttle:6,1')
              ->name('user-password.update');

         Route::inertia('settings/appearance', 'settings/Appearance')
              ->name('appearance.edit');
     });
