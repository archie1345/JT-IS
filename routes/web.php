<?php

use App\Http\Controllers\ClientsPageController;
use App\Http\Controllers\DashboardLayoutController;
use App\Http\Controllers\ProjectDetailsController;
use App\Http\Controllers\ProjectsPageController;
use App\Http\Controllers\ProjectMonitoring\UsersController;
use App\Http\Controllers\ProjectMonitoring\TendersController;
use App\Http\Controllers\ProjectMonitoring\ClientsController;
use App\Http\Controllers\ProjectMonitoring\RapsController;
use App\Http\Controllers\ProjectMonitoring\InvoicesController;
use App\Http\Controllers\ProjectMonitoring\FundRequestsController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

// Halaman Publik
Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

// Rute yang butuh Login
Route::middleware(['auth', 'verified'])->group(function () {
    
    // 1. DASHBOARD 
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    Route::post('dashboard/layout', [DashboardLayoutController::class, 'store'])
        ->name('dashboard.layout.store');
    Route::inertia('billing-test', 'BillingTest')->name('billing.test');
    Route::inertia('rab-rap', 'RabRap')->name('rab-rap');
    Route::get('projects', ProjectsPageController::class)->name('projects');
    Route::get('projects/create', [ProjectDetailsController::class, 'create'])->name('projects.create');
    Route::post('projects', [ProjectDetailsController::class, 'store'])->name('projects.store');
    Route::get('projects/{project}', [ProjectDetailsController::class, 'show'])->name('projects.show');
    Route::patch('projects/{project}', [ProjectDetailsController::class, 'update'])->name('projects.update');
    Route::post('projects/{project}/documents', [ProjectDetailsController::class, 'storeDocument'])->name('projects.documents.store');
    Route::inertia('settings', 'Settings')->name('settings');
    Route::inertia('settings/profile', 'SettingsProfile')->name('settings.profile');
    Route::inertia('settings/account', 'SettingsAccount')->name('settings.account');
    Route::get('client', ClientsPageController::class)->name('client');

    // 2. MODUL MARKETING 
    Route::group([], function () {
        Route::get('pipeline', [TendersController::class, 'index'])->name('pipeline');
        Route::resource('clients-data', ClientsController::class)->names('clients-data');
    });

    // 3. MODUL OPERASIONAL
    Route::group([], function () {
        Route::get('rab-rap', [RapsController::class, 'index'])->name('rab-rap');
        // Rute untuk pengajuan dana (RPU)
        Route::resource('fund-requests', FundRequestsController::class);
    });

    // 4. MODUL KEUANGAN 
    Route::group([], function () {
        Route::resource('invoices', InvoicesController::class);
        Route::inertia('billing-test', 'BillingTest')->name('billing.test');
    });

    // 5. MODUL ADMINISTRATOR 
    Route::group([], function () {
        Route::resource('users', UsersController::class);
    });
});

Route::inertia('/prototype', 'Prototype')->name('prototype');

require __DIR__.'/settings.php';
