<?php

use App\Http\Controllers\ClientsPageController;
use App\Http\Controllers\DashboardLayoutController;
use App\Http\Controllers\ProjectsPageController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    Route::post('dashboard/layout', [DashboardLayoutController::class, 'store'])
        ->name('dashboard.layout.store');
    Route::inertia('billing-test', 'BillingTest')->name('billing.test');
    Route::inertia('rab-rap', 'RabRap')->name('rab-rap');
    Route::get('projects', ProjectsPageController::class)->name('projects');
    Route::inertia('settings', 'Settings')->name('settings');
    Route::inertia('settings/profile', 'SettingsProfile')->name('settings.profile');
    Route::inertia('settings/account', 'SettingsAccount')->name('settings.account');
    Route::get('client', ClientsPageController::class)->name('client');
});

Route::inertia('/prototype', 'Prototype')->name('prototype');
// Route::inertia('/prototype/login', 'Login')->name('prototype.login');

require __DIR__.'/settings.php';
