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

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')
        ->middleware('permission:page.dashboard.view')
        ->name('dashboard');

    Route::post('dashboard/layout', [DashboardLayoutController::class, 'store'])
        ->name('dashboard.layout.store');

    Route::inertia('settings', 'Settings')->name('settings');
    Route::inertia('settings/profile', 'SettingsProfile')->name('settings.profile');
    Route::inertia('settings/account', 'SettingsAccount')->name('settings.account');

    Route::middleware(['role:admin|employee'])->group(function () {
        Route::inertia('billing-test', 'BillingTest')
            ->middleware('permission:page.billing-test.view')
            ->name('billing.test');

        Route::get('rabs', RabsPageController::class)
            ->middleware('permission:page.rabs.view')
            ->name('rabs');
        Route::get('rabs/{rab}', [RabRapDetailsController::class, 'showRab'])
            ->middleware('permission:page.rabs.view')
            ->name('rabs.show');

        Route::get('raps', RapsPageController::class)
            ->middleware('permission:page.raps.view')
            ->name('raps');
        Route::get('raps/{rap}', [RabRapDetailsController::class, 'showRap'])
            ->middleware('permission:page.raps.view')
            ->name('raps.show');

        Route::get('projects', ProjectsPageController::class)
            ->middleware('permission:page.projects.view')
            ->name('projects');
        Route::get('projects/create', [ProjectDetailsController::class, 'create'])
            ->middleware('permission:action.projects.create')
            ->name('projects.create');
        Route::post('projects', [ProjectDetailsController::class, 'store'])
            ->middleware('permission:action.projects.create')
            ->name('projects.store');
        Route::get('projects/{project}', [ProjectDetailsController::class, 'show'])
            ->middleware('permission:page.projects.view')
            ->name('projects.show');
        Route::patch('projects/{project}', [ProjectDetailsController::class, 'update'])
            ->middleware('permission:action.projects.update')
            ->name('projects.update');
        Route::post('projects/{project}/documents', [ProjectDetailsController::class, 'storeDocument'])
            ->middleware('permission:action.projects.update')
            ->name('projects.documents.store');
        Route::get('projects/documents/{projectDocument}', [ProjectDetailsController::class, 'showDocument'])
            ->middleware('permission:page.projects.view')
            ->name('projects.documents.show');

        Route::get('client', ClientsPageController::class)
            ->middleware('permission:page.clients.view')
            ->name('client');
        Route::get('client/create', [ClientDetailsController::class, 'create'])
            ->middleware('permission:action.clients.create')
            ->name('client.create');
        Route::post('client', [ClientDetailsController::class, 'store'])
            ->middleware('permission:action.clients.create')
            ->name('client.store');
        Route::get('client/{client}', [ClientDetailsController::class, 'show'])
            ->middleware('permission:page.clients.view')
            ->name('client.show');
        Route::patch('client/{client}', [ClientDetailsController::class, 'update'])
            ->middleware('permission:action.clients.update')
            ->name('client.update');

        Route::get('pipeline', [TendersController::class, 'index'])
            ->middleware('permission:page.pipeline.view')
            ->name('pipeline');
        Route::resource('clients-data', ClientsController::class)
            ->middleware('permission:page.clients.view')
            ->names('clients-data');

        Route::resource('fund-requests', FundRequestsController::class)
            ->middleware('permission:page.fund-requests.view');

        Route::resource('invoices', InvoicesController::class)
            ->middleware('permission:page.invoices.view');
    });

    Route::middleware(['role:admin', 'permission:page.admin.accounts.view'])->group(function () {
        Route::get('Admin_acc_mgmt', [AdminAccMgmtController::class, 'index'])->name('admin.acc_mgmt');
        Route::post('Admin_acc_mgmt', [AdminAccMgmtController::class, 'store'])
            ->middleware('permission:action.admin.accounts.create')
            ->name('admin.acc_mgmt.store');
        Route::patch('Admin_acc_mgmt/{user}', [AdminAccMgmtController::class, 'update'])
            ->middleware('permission:action.admin.accounts.update')
            ->name('admin.acc_mgmt.update');
        Route::delete('Admin_acc_mgmt/{user}', [AdminAccMgmtController::class, 'destroy'])
            ->middleware('permission:action.admin.accounts.delete')
            ->name('admin.acc_mgmt.destroy');
        Route::patch('Admin_acc_mgmt/roles/{role}', [AdminAccMgmtController::class, 'updateRolePermissions'])
            ->whereNumber('role')
            ->middleware('permission:action.admin.roles.manage')
            ->name('admin.acc_mgmt.roles.update');
    });
});

Route::inertia('/prototype', 'Prototype')->name('prototype');

require __DIR__.'/settings.php';
