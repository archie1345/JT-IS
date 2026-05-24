<?php

use App\Http\Controllers\AdminAccMgmtController;
use App\Http\Controllers\AiDocumentExtractionController;
use App\Http\Controllers\ClientDetailsController;
use App\Http\Controllers\ClientsPageController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DashboardLayoutController;
use App\Http\Controllers\ProjectDetailsController;
use App\Http\Controllers\ProjectDocumentsController;
use App\Http\Controllers\ProjectsPageController;
use App\Http\Controllers\ProjectMonitoring\ClientsController;
use App\Http\Controllers\ProjectMonitoring\FundRequestsController;
use App\Http\Controllers\ProjectMonitoring\InvoiceItemsController;
use App\Http\Controllers\ProjectMonitoring\InvoicesController;
use App\Http\Controllers\ProjectMonitoring\ProgressReportsController;
use App\Http\Controllers\ProjectMonitoring\ProjectCostsController;
use App\Http\Controllers\ProjectMonitoring\ProjectCostItemsController;
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

// Welcome page: resources/js/pages/Welcome.vue
Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard: DashboardController.php, DashboardLayoutController.php; resources/js/pages/Dashboard.vue
    Route::get('dashboard', DashboardController::class)
        ->middleware('permission:page.dashboard.view')
        ->name('dashboard');

    Route::post('dashboard/layout', [DashboardLayoutController::class, 'store'])
        ->name('dashboard.layout.store');

    // Settings shell pages: Inertia page keys only; detailed settings routes live in routes/settings.php.
    Route::inertia('settings', 'Settings')->name('settings');
    Route::inertia('settings/profile', 'SettingsProfile')->name('settings.profile');
    Route::inertia('settings/account', 'SettingsAccount')->name('settings.account');

    Route::middleware(['role:admin|employee'])->group(function () {
        // Billing test page: resources/js/pages/BillingTest.vue
        Route::inertia('billing-test', 'BillingTest')
            ->middleware('permission:page.billing-test.view')
            ->name('billing.test');

        // RAB: RabsPageController.php, ProjectMonitoring/RabsController.php; resources/js/pages/Rabs.vue
        // Detail view: RabRapDetailsController.php; resources/js/pages/RabRapDetails.vue
        Route::prefix('rabs')->group(function () {
            Route::get('/', RabsPageController::class)
                ->middleware('permission:page.rabs.view')
                ->name('rabs');
            Route::post('/', [RabsController::class, 'store'])
                ->middleware('permission:action.rabs.create')
                ->name('rabs.store');
            Route::get('{rab}', [RabRapDetailsController::class, 'showRab'])
                ->middleware('permission:page.rabs.view')
                ->name('rabs.show');
            Route::patch('{id}', [RabsController::class, 'update'])
                ->whereNumber('id')
                ->middleware('permission:action.rabs.update')
                ->name('rabs.update');
            Route::delete('{id}', [RabsController::class, 'destroy'])
                ->whereNumber('id')
                ->middleware('permission:action.rabs.delete')
                ->name('rabs.destroy');
            Route::post('{rab}/items', [RabItemsController::class, 'store'])
                ->middleware('permission:action.rabs.update')
                ->name('rabs.items.store');
        });

        // RAB items: ProjectMonitoring/RabItemsController.php
        Route::prefix('rab-items')
            ->name('rabs.items.')
            ->middleware('permission:action.rabs.update')
            ->group(function () {
                Route::patch('{id}', [RabItemsController::class, 'update'])
                    ->whereNumber('id')
                    ->name('update');
                Route::delete('{id}', [RabItemsController::class, 'destroy'])
                    ->whereNumber('id')
                    ->name('destroy');
            });

        // RAP: RapsPageController.php, ProjectMonitoring/RapsController.php; resources/js/pages/Raps.vue
        // Detail view: RabRapDetailsController.php; resources/js/pages/RabRapDetails.vue
        Route::prefix('raps')->group(function () {
            Route::get('/', RapsPageController::class)
                ->middleware('permission:page.raps.view')
                ->name('raps');
            Route::post('/', [RapsController::class, 'store'])
                ->middleware('permission:action.raps.create')
                ->name('raps.store');
            Route::get('{rap}', [RabRapDetailsController::class, 'showRap'])
                ->middleware('permission:page.raps.view')
                ->name('raps.show');
            Route::patch('{id}', [RapsController::class, 'update'])
                ->whereNumber('id')
                ->middleware('permission:action.raps.update')
                ->name('raps.update');
            Route::delete('{id}', [RapsController::class, 'destroy'])
                ->whereNumber('id')
                ->middleware('permission:action.raps.delete')
                ->name('raps.destroy');
            Route::post('{rap}/items', [RapItemsController::class, 'store'])
                ->middleware('permission:action.raps.update')
                ->name('raps.items.store');
        });

        // RAP items: ProjectMonitoring/RapItemsController.php
        Route::prefix('rap-items')
            ->name('raps.items.')
            ->middleware('permission:action.raps.update')
            ->group(function () {
                Route::patch('{id}', [RapItemsController::class, 'update'])
                    ->whereNumber('id')
                    ->name('update');
                Route::delete('{id}', [RapItemsController::class, 'destroy'])
                    ->whereNumber('id')
                    ->name('destroy');
            });

        // Projects: ProjectsPageController.php, ProjectDetailsController.php; resources/js/pages/Projects.vue, ProjectDetails.vue
        Route::prefix('projects')->group(function () {
            Route::get('/', ProjectsPageController::class)
                ->middleware('permission:page.projects.view')
                ->name('projects');
            Route::get('create', [ProjectDetailsController::class, 'create'])
                ->middleware('permission:action.projects.create')
                ->name('projects.create');
            Route::post('/', [ProjectDetailsController::class, 'store'])
                ->middleware('permission:action.projects.create')
                ->name('projects.store');
            Route::get('{project}', [ProjectDetailsController::class, 'show'])
                ->middleware('permission:page.projects.view')
                ->name('projects.show');
            Route::patch('{project}', [ProjectDetailsController::class, 'update'])
                ->middleware('permission:action.projects.update')
                ->name('projects.update');

            // Project documents: ProjectDocumentsController.php
            Route::prefix('documents')->name('projects.documents.')->group(function () {
                Route::post('ocr', [ProjectDocumentsController::class, 'ocr'])
                    ->middleware('permission:action.projects.update')
                    ->name('ocr');
                Route::post('apply-extraction', [ProjectDocumentsController::class, 'applyExtraction'])
                    ->middleware('permission:action.projects.update')
                    ->name('apply-extraction');
                Route::get('{projectDocument}', [ProjectDocumentsController::class, 'show'])
                    ->middleware('permission:page.projects.view')
                    ->name('show');
                Route::delete('{projectDocument}', [ProjectDocumentsController::class, 'destroy'])
                    ->middleware('permission:action.projects.update')
                    ->name('destroy');
            });

            Route::post('{project}/documents', [ProjectDocumentsController::class, 'store'])
                ->middleware('permission:action.projects.update')
                ->name('projects.documents.store');
        });

        // Clients: ClientsPageController.php, ClientDetailsController.php; resources/js/pages/Clients.vue, ClientsDetails.vue
        Route::prefix('client')->group(function () {
            Route::get('/', ClientsPageController::class)
                ->middleware('permission:page.clients.view')
                ->name('client');
            Route::get('create', [ClientDetailsController::class, 'create'])
                ->middleware('permission:action.clients.create')
                ->name('client.create');
            Route::post('/', [ClientDetailsController::class, 'store'])
                ->middleware('permission:action.clients.create')
                ->name('client.store');
            Route::get('{client}', [ClientDetailsController::class, 'show'])
                ->middleware('permission:page.clients.view')
                ->name('client.show');
            Route::patch('{client}', [ClientDetailsController::class, 'update'])
                ->middleware('permission:action.clients.update')
                ->name('client.update');
        });

        // Pipeline: ProjectMonitoring/TendersController.php, TableCrudController.php; resources/js/pages/Pipeline.vue, PrototypeRecordDetails.vue
        Route::prefix('pipeline')->group(function () {
            Route::get('/', [TendersController::class, 'index'])
                ->middleware('permission:page.pipeline.view')
                ->name('pipeline');
            Route::post('/', [TendersController::class, 'store'])
                ->middleware('permission:action.pipeline.create')
                ->name('pipeline.store');
            Route::get('{id}', [TendersController::class, 'show'])
                ->whereNumber('id')
                ->middleware('permission:page.pipeline.view')
                ->name('pipeline.show');
            Route::patch('{id}', [TendersController::class, 'update'])
                ->whereNumber('id')
                ->middleware('permission:action.pipeline.update')
                ->name('pipeline.update');
            Route::delete('{id}', [TendersController::class, 'destroy'])
                ->whereNumber('id')
                ->middleware('permission:action.pipeline.delete')
                ->name('pipeline.destroy');
        });

        // Clients data resource: ProjectMonitoring/ClientsController.php, TableCrudController.php; resources/js/pages/Clients.vue
        Route::resource('clients-data', ClientsController::class)
            ->middleware('permission:page.clients.view')
            ->only(['index', 'store', 'show', 'update', 'destroy'])
            ->names('clients-data');

        // Fund requests resource: ProjectMonitoring/FundRequestsController.php, TableCrudController.php
        Route::resource('fund-requests', FundRequestsController::class)
            ->middleware('permission:page.fund-requests.view')
            ->only(['index', 'store', 'show', 'update', 'destroy']);

        // Invoices: ProjectMonitoring/InvoicesController.php, TableCrudController.php; resources/js/pages/Invoices.vue, FinancialDocumentDetails.vue
        Route::resource('invoices', InvoicesController::class)
            ->only(['index', 'store', 'show', 'update', 'destroy'])
            ->middlewareFor(['index', 'show'], 'permission:page.invoices.view')
            ->middlewareFor('store', 'permission:action.invoices.create')
            ->middlewareFor('update', 'permission:action.invoices.update')
            ->middlewareFor('destroy', 'permission:action.invoices.delete');

        // Invoice items: ProjectMonitoring/InvoiceItemsController.php
        Route::prefix('invoices/{invoice}/items')
            ->name('invoices.items.')
            ->group(function () {
                Route::post('/', [InvoiceItemsController::class, 'store'])
                    ->middleware('permission:action.invoices.update')
                    ->name('store');
            });

        Route::prefix('invoice-items')
            ->name('invoices.items.')
            ->middleware('permission:action.invoices.update')
            ->group(function () {
                Route::patch('{id}', [InvoiceItemsController::class, 'update'])
                    ->whereNumber('id')
                    ->name('update');
                Route::delete('{id}', [InvoiceItemsController::class, 'destroy'])
                    ->whereNumber('id')
                    ->name('destroy');
            });

        // Project costs: ProjectMonitoring/ProjectCostsController.php, TableCrudController.php; resources/js/pages/ProjectCosts.vue, FinancialDocumentDetails.vue
        Route::prefix('project-costs')->name('project-costs.')->group(function () {
            Route::get('/', [ProjectCostsController::class, 'index'])
                ->middleware('permission:sidebar.finance.cost-realization.view')
                ->name('index');
            Route::post('/', [ProjectCostsController::class, 'store'])
                ->middleware('permission:action.project-costs.create')
                ->name('store');
            Route::get('{id}', [ProjectCostsController::class, 'show'])
                ->whereNumber('id')
                ->middleware('permission:sidebar.finance.cost-realization.view')
                ->name('show');
            Route::patch('{id}', [ProjectCostsController::class, 'update'])
                ->whereNumber('id')
                ->middleware('permission:action.project-costs.update')
                ->name('update');
            Route::delete('{id}', [ProjectCostsController::class, 'destroy'])
                ->whereNumber('id')
                ->middleware('permission:action.project-costs.delete')
                ->name('destroy');
            Route::post('{projectCost}/items', [ProjectCostItemsController::class, 'store'])
                ->middleware('permission:action.project-costs.update')
                ->name('items.store');
        });

        // Project cost items: ProjectMonitoring/ProjectCostItemsController.php
        Route::prefix('project-cost-items')
            ->name('project-costs.items.')
            ->middleware('permission:action.project-costs.update')
            ->group(function () {
                Route::patch('{id}', [ProjectCostItemsController::class, 'update'])
                    ->whereNumber('id')
                    ->name('update');
                Route::delete('{id}', [ProjectCostItemsController::class, 'destroy'])
                    ->whereNumber('id')
                    ->name('destroy');
            });

        // Progress updates: ProjectMonitoring/ProgressReportsController.php, TableCrudController.php; resources/js/pages/ProgressUpdates.vue, PrototypeRecordDetails.vue
        Route::prefix('progress-updates')->name('progress-updates.')->group(function () {
            Route::get('/', [ProgressReportsController::class, 'index'])
                ->middleware('permission:sidebar.operational.progress.view')
                ->name('index');
            Route::post('/', [ProgressReportsController::class, 'store'])
                ->middleware('permission:action.progress-updates.create')
                ->name('store');
            Route::get('{id}', [ProgressReportsController::class, 'show'])
                ->whereNumber('id')
                ->middleware('permission:sidebar.operational.progress.view')
                ->name('show');
            Route::patch('{id}', [ProgressReportsController::class, 'update'])
                ->whereNumber('id')
                ->middleware('permission:action.progress-updates.update')
                ->name('update');
            Route::delete('{id}', [ProgressReportsController::class, 'destroy'])
                ->whereNumber('id')
                ->middleware('permission:action.progress-updates.delete')
                ->name('destroy');
        });

        // AI document extraction: AiDocumentExtractionController.php; resources/js/pages/AiDocumentExtraction.vue
        Route::prefix('ai-document-extraction')->group(function () {
            Route::get('/', [AiDocumentExtractionController::class, 'index'])
                ->middleware('permission:sidebar.operational.progress.view')
                ->name('ai-document-extraction');
            Route::post('ocr', [AiDocumentExtractionController::class, 'ocr'])
                ->middleware('permission:sidebar.operational.progress.view')
                ->name('ai-document-extraction.ocr');
            Route::post('budget-items', [AiDocumentExtractionController::class, 'storeBudgetItems'])
                ->middleware('permission:action.projects.update')
                ->name('ai-document-extraction.budget-items');
        });
    });

    Route::middleware(['role:admin', 'permission:page.admin.accounts.view'])->group(function () {
        // Admin account management: AdminAccMgmtController.php; resources/js/pages/AdmnUsrMgmtPbac.vue
        Route::prefix('Admin_acc_mgmt')->group(function () {
            Route::get('/', [AdminAccMgmtController::class, 'index'])
                ->name('admin.acc_mgmt');
            Route::post('/', [AdminAccMgmtController::class, 'store'])
                ->middleware('permission:action.admin.accounts.create')
                ->name('admin.acc_mgmt.store');
            Route::patch('{user}', [AdminAccMgmtController::class, 'update'])
                ->middleware('permission:action.admin.accounts.update')
                ->name('admin.acc_mgmt.update');
            Route::delete('{user}', [AdminAccMgmtController::class, 'destroy'])
                ->middleware('permission:action.admin.accounts.delete')
                ->name('admin.acc_mgmt.destroy');
            Route::patch('roles/{role}', [AdminAccMgmtController::class, 'updateRolePermissions'])
                ->whereNumber('role')
                ->middleware('permission:action.admin.roles.manage')
                ->name('admin.acc_mgmt.roles.update');
        });
    });
});

// Settings account/profile/password/2FA routes: routes/settings.php
require __DIR__.'/settings.php';
