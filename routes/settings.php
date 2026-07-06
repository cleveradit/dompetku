<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Identity\Presentation\Http\Controllers\ProfileController;
use Modules\Identity\Presentation\Http\Controllers\SettingsController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('settings/password', [SettingsController::class, 'password'])->name('password.edit');
    Route::get('settings/appearance', [SettingsController::class, 'appearance'])->name('appearance');

    Route::get('settings/account', [SettingsController::class, 'account'])->name('account.edit');
    Route::delete('settings/account', [ProfileController::class, 'destroy'])->name('account.destroy');
});
