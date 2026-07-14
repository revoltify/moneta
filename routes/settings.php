<?php

declare(strict_types=1);

use App\Http\Controllers\Companies\CompanyController;
use App\Http\Controllers\Settings\ApiTokenController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use App\Http\Middleware\SetCurrentCompany;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/security', [SecurityController::class, 'edit'])
        ->middleware(RequirePassword::class)
        ->name('security.edit');

    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::get('settings/api-tokens', [ApiTokenController::class, 'index'])->name('api-tokens.index');
    Route::post('settings/api-tokens', [ApiTokenController::class, 'store'])->name('api-tokens.store');
    Route::delete('settings/api-tokens/{tokenId}', [ApiTokenController::class, 'destroy'])->name('api-tokens.destroy');

    Route::get('settings/companies', [CompanyController::class, 'index'])->name('companies.index');
    Route::post('settings/companies', [CompanyController::class, 'store'])->name('companies.store');

    Route::middleware(SetCurrentCompany::class)->group(function () {
        Route::get('settings/companies/{company}', [CompanyController::class, 'edit'])->name('companies.edit');
        Route::patch('settings/companies/{company}', [CompanyController::class, 'update'])->name('companies.update');
        Route::patch('settings/companies/{company}/preferences', [CompanyController::class, 'updatePreferences'])->name('companies.preferences.update');
        Route::delete('settings/companies/{company}', [CompanyController::class, 'destroy'])->name('companies.destroy');
        Route::post('settings/companies/{company}/switch', [CompanyController::class, 'switch'])->name('companies.switch');
    });
});

Route::get('.well-known/passkey-endpoints', function () {
    return response()->json([
        'enroll' => route('security.edit'),
        'manage' => route('security.edit'),
    ]);
})->name('well-known.passkeys');
