<?php

use App\Http\Controllers\AdminAccMgmtController;
use App\Http\Controllers\AiDocumentExtractionController;
use App\Http\Controllers\ClientDetailsController;
use App\Http\Controllers\ClientsPageController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DashboardLayoutController;
use App\Http\Controllers\ProfitLossController;
use App\Http\Controllers\ProjectDetailsController;
use App\Http\Controllers\ProjectDocumentsController;
use App\Http\Controllers\ProjectsPageController;
use App\Http\Controllers\ProjectMonitoring\ClientsController;
use App\Http\Controllers\ProjectMonitoring\FundRequestsController;
use App\Http\Controllers\ProjectMonitoring\InvoicesController;
use App\Http\Controllers\ProjectMonitoring\ProgressReportsController;
use App\Http\Controllers\ProjectMonitoring\ProjectCostsController;
use App\Http\Controllers\ProjectMonitoring\RabItemsController;
use App\Http\Controllers\ProjectMonitoring\RapItemsController;
use App\Http\Controllers\ProjectMonitoring\RabsController;
use App\Http\Controllers\ProjectMonitoring\RapsController;
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
    Route::get('dashboard', DashboardController::class)
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
        Route::post('rabs', [RabsController::class, 'store'])
            ->middleware('permission:action.rabs.create')
            ->name('rabs.store');
        Route::get('rabs/{rab}', [RabRapDetailsController::class, 'showRab'])
            ->middleware('permission:page.rabs.view')
            ->name('rabs.show');
        Route::patch('rabs/{id}', [RabsController::class, 'update'])
            ->whereNumber('id')
            ->middleware('permission:action.rabs.update')
            ->name('rabs.update');
        Route::delete('rabs/{id}', [RabsController::class, 'destroy'])
            ->whereNumber('id')
            ->middleware('permission:action.rabs.delete')
            ->name('rabs.destroy');
        Route::post('rabs/{rab}/items', [RabItemsController::class, 'store'])
            ->middleware('permission:action.rabs.update')
            ->name('rabs.items.store');
        Route::patch('rab-items/{id}', [RabItemsController::class, 'update'])
            ->whereNumber('id')
            ->middleware('permission:action.rabs.update')
            ->name('rabs.items.update');
        Route::delete('rab-items/{id}', [RabItemsController::class, 'destroy'])
            ->whereNumber('id')
            ->middleware('permission:action.rabs.update')
            ->name('rabs.items.destroy');

        Route::get('raps', RapsPageController::class)
            ->middleware('permission:page.raps.view')
            ->name('raps');
        Route::post('raps', [RapsController::class, 'store'])
            ->middleware('permission:action.raps.create')
            ->name('raps.store');
        Route::get('raps/{rap}', [RabRapDetailsController::class, 'showRap'])
            ->middleware('permission:page.raps.view')
            ->name('raps.show');
        Route::patch('raps/{id}', [RapsController::class, 'update'])
            ->whereNumber('id')
            ->middleware('permission:action.raps.update')
            ->name('raps.update');
        Route::delete('raps/{id}', [RapsController::class, 'destroy'])
            ->whereNumber('id')
            ->middleware('permission:action.raps.delete')
            ->name('raps.destroy');
        Route::post('raps/{rap}/items', [RapItemsController::class, 'store'])
            ->middleware('permission:action.raps.update')
            ->name('raps.items.store');
        Route::patch('rap-items/{id}', [RapItemsController::class, 'update'])
            ->whereNumber('id')
            ->middleware('permission:action.raps.update')
            ->name('raps.items.update');
        Route::delete('rap-items/{id}', [RapItemsController::class, 'destroy'])
            ->whereNumber('id')
            ->middleware('permission:action.raps.update')
            ->name('raps.items.destroy');

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
        Route::post('projects/{project}/documents', [ProjectDocumentsController::class, 'store'])
            ->middleware('permission:action.projects.update')
            ->name('projects.documents.store');
        Route::post('projects/documents/ocr', [ProjectDocumentsController::class, 'ocr'])
            ->middleware('permission:action.projects.update')
            ->name('projects.documents.ocr');
        Route::post('projects/documents/apply-extraction', [ProjectDocumentsController::class, 'applyExtraction'])
            ->middleware('permission:action.projects.update')
            ->name('projects.documents.apply-extraction');
        Route::get('projects/documents/{projectDocument}', [ProjectDocumentsController::class, 'show'])
            ->middleware('permission:page.projects.view')
            ->name('projects.documents.show');
        Route::delete('projects/documents/{projectDocument}', [ProjectDocumentsController::class, 'destroy'])
            ->middleware('permission:action.projects.update')
            ->name('projects.documents.destroy');

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
        Route::post('pipeline', [TendersController::class, 'store'])
            ->middleware('permission:action.pipeline.create')
            ->name('pipeline.store');
        Route::patch('pipeline/{id}', [TendersController::class, 'update'])
            ->middleware('permission:action.pipeline.update')
            ->name('pipeline.update');
        Route::delete('pipeline/{id}', [TendersController::class, 'destroy'])
            ->middleware('permission:action.pipeline.delete')
            ->name('pipeline.destroy');
        Route::resource('clients-data', ClientsController::class)
            ->middleware('permission:page.clients.view')
            ->only(['index', 'store', 'show', 'update', 'destroy'])
            ->names('clients-data');

        Route::resource('fund-requests', FundRequestsController::class)
            ->middleware('permission:page.fund-requests.view')
            ->only(['index', 'store', 'show', 'update', 'destroy']);

        Route::resource('invoices', InvoicesController::class)
            ->only(['index', 'store', 'show', 'update', 'destroy'])
            ->middlewareFor(['index', 'show'], 'permission:page.invoices.view')
            ->middlewareFor('store', 'permission:action.invoices.create')
            ->middlewareFor('update', 'permission:action.invoices.update')
            ->middlewareFor('destroy', 'permission:action.invoices.delete');

        Route::get('project-costs', [ProjectCostsController::class, 'index'])
            ->middleware('permission:sidebar.finance.cost-realization.view')
            ->name('project-costs.index');
        Route::post('project-costs', [ProjectCostsController::class, 'store'])
            ->middleware('permission:action.project-costs.create')
            ->name('project-costs.store');
        Route::patch('project-costs/{id}', [ProjectCostsController::class, 'update'])
            ->middleware('permission:action.project-costs.update')
            ->name('project-costs.update');
        Route::delete('project-costs/{id}', [ProjectCostsController::class, 'destroy'])
            ->middleware('permission:action.project-costs.delete')
            ->name('project-costs.destroy');

        Route::get('profit-loss', ProfitLossController::class)
            ->middleware('permission:sidebar.finance.profit-loss.view')
            ->name('profit-loss');

        Route::get('progress-updates', [ProgressReportsController::class, 'index'])
            ->middleware('permission:sidebar.operational.progress.view')
            ->name('progress-updates.index');
        Route::get('ai-document-extraction', [AiDocumentExtractionController::class, 'index'])
            ->middleware('permission:sidebar.operational.progress.view')
            ->name('ai-document-extraction');
        Route::post('ai-document-extraction/ocr', [AiDocumentExtractionController::class, 'ocr'])
            ->middleware('permission:sidebar.operational.progress.view')
            ->name('ai-document-extraction.ocr');
        Route::post('ai-document-extraction/budget-items', [AiDocumentExtractionController::class, 'storeBudgetItems'])
            ->middleware('permission:action.projects.update')
            ->name('ai-document-extraction.budget-items');
        Route::post('progress-updates', [ProgressReportsController::class, 'store'])
            ->middleware('permission:action.progress-updates.create')
            ->name('progress-updates.store');
        Route::patch('progress-updates/{id}', [ProgressReportsController::class, 'update'])
            ->middleware('permission:action.progress-updates.update')
            ->name('progress-updates.update');
        Route::delete('progress-updates/{id}', [ProgressReportsController::class, 'destroy'])
            ->middleware('permission:action.progress-updates.delete')
            ->name('progress-updates.destroy');
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
