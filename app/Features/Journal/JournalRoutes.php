<?php

use App\_Core\Middleware\RoleMiddleware;
use App\Features\Journal\JournalController;
use App\Features\User\UserService;
use Illuminate\Support\Facades\Route;

Route::controller(JournalController::class)
     ->middleware(RoleMiddleware::roles([UserService::role_super_admin]))
     ->where(['cle' => '[A-Za-z0-9]{32}'])
     ->group(function () {
         Route::get('journal', 'list')
              ->name('journal.list');
         Route::get('journal/{cle}', 'detail')
              ->name('journal.detail');
     });
