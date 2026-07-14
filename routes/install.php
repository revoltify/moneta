<?php

use App\Http\Controllers\Install\InstallerController;
use App\Http\Middleware\EnsureNotInstalled;
use Illuminate\Support\Facades\Route;

Route::prefix('install')
    ->name('install.')
    ->middleware(EnsureNotInstalled::class)
    ->group(function () {
        Route::get('/', [InstallerController::class, 'requirements'])->name('index');
        Route::get('database', [InstallerController::class, 'database'])->name('database');
        Route::post('database', [InstallerController::class, 'storeDatabase'])->name('database.store');
        Route::get('migrations', [InstallerController::class, 'migrations'])->name('migrations');
        Route::post('migrations', [InstallerController::class, 'runMigrations'])->name('migrations.run');
        Route::get('admin', [InstallerController::class, 'admin'])->name('admin');
        Route::post('admin', [InstallerController::class, 'storeAdmin'])->name('admin.store');
    });
