<?php

use App\Http\Controllers\AdminAccMgmtController;
use App\Http\Controllers\ClientDetailsController;
use App\Http\Controllers\ClientsPageController;
use App\Http\Controllers\DashboardLayoutController;
use App\Http\Controllers\ProjectDetailsController;
use App\Http\Controllers\ProjectsPageController;
use App\Http\Controllers\ProjectMonitoring\ClientsController;
use App\Http\Controllers\ProjectMonitoring\FundRequestsController;
use App\Http\Controllers\ProjectMonitoring\InvoicesController;
use App\Http\Controllers\ProjectMonitoring\TendersController;
use App\Http\Controllers\RabRapDetailsController;
use App\Http\Controllers\RabsPageController;
use App\Http\Controllers\RapsPageController;
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
    Route::inertia('settings', 'Settings')->name('settings');
    Route::inertia('settings/profile', 'SettingsProfile')->name('settings.profile');
    Route::inertia('settings/account', 'SettingsAccount')->name('settings.account');

    Route::middleware(['role:admin|employee'])->group(function () {
        Route::inertia('billing-test', 'BillingTest')->name('billing.test');
        Route::get('rabs', RabsPageController::class)->name('rabs');
        Route::get('rabs/{rab}', [RabRapDetailsController::class, 'showRab'])->name('rabs.show');
        Route::get('raps', RapsPageController::class)->name('raps');
        Route::get('raps/{rap}', [RabRapDetailsController::class, 'showRap'])->name('raps.show');
        Route::get('projects', ProjectsPageController::class)->name('projects');
        Route::get('projects/create', [ProjectDetailsController::class, 'create'])->name('projects.create');
        Route::post('projects', [ProjectDetailsController::class, 'store'])->name('projects.store');
        Route::get('projects/{project}', [ProjectDetailsController::class, 'show'])->name('projects.show');
        Route::patch('projects/{project}', [ProjectDetailsController::class, 'update'])->name('projects.update');
        Route::post('projects/{project}/documents', [ProjectDetailsController::class, 'storeDocument'])->name('projects.documents.store');
        Route::get('projects/documents/{projectDocument}', [ProjectDetailsController::class, 'showDocument'])->name('projects.documents.show');
        Route::get('client', ClientsPageController::class)->name('client');
        Route::get('client/create', [ClientDetailsController::class, 'create'])->name('client.create');
        Route::post('client', [ClientDetailsController::class, 'store'])->name('client.store');
        Route::get('client/{client}', [ClientDetailsController::class, 'show'])->name('client.show');
        Route::patch('client/{client}', [ClientDetailsController::class, 'update'])->name('client.update');

        // 2. MODUL MARKETING
        Route::group([], function () {
            Route::get('pipeline', [TendersController::class, 'index'])->name('pipeline');
            Route::resource('clients-data', ClientsController::class)->names('clients-data');
        });

        // 3. MODUL OPERASIONAL
        Route::group([], function () {
            // Rute untuk pengajuan dana (RPU)
            Route::resource('fund-requests', FundRequestsController::class);
        });

        // 4. MODUL KEUANGAN
        Route::group([], function () {
            Route::resource('invoices', InvoicesController::class);
        });
    });

    Route::middleware(['role:admin'])->group(function () {
        Route::get('Admin_acc_mgmt', [AdminAccMgmtController::class, 'index'])->name('admin.acc_mgmt');
        Route::post('Admin_acc_mgmt', [AdminAccMgmtController::class, 'store'])->name('admin.acc_mgmt.store');
        Route::patch('Admin_acc_mgmt/{user}', [AdminAccMgmtController::class, 'update'])->name('admin.acc_mgmt.update');
        Route::delete('Admin_acc_mgmt/{user}', [AdminAccMgmtController::class, 'destroy'])->name('admin.acc_mgmt.destroy');
    });
});

Route::inertia('/prototype', 'Prototype')->name('prototype');

require __DIR__.'/settings.php';
