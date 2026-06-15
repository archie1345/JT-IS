<?php

use App\Http\Controllers\AiDocumentExtractionController;
use App\Http\Controllers\ProjectDetailsController;
use App\Http\Controllers\ProjectDocumentsController;
use App\Http\Controllers\ProjectsPageController;
use App\Http\Controllers\ProjectMonitoring\FundRequestsController;
use App\Http\Controllers\ProjectMonitoring\InvoiceItemsController;
use App\Http\Controllers\ProjectMonitoring\InvoicesController;
use App\Http\Controllers\ProjectMonitoring\ProgressReportsController;
use App\Http\Controllers\ProjectMonitoring\ProjectCostItemsController;
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

Route::middleware(['auth', 'verified', 'role:admin|employee'])->group(function (): void {
    Route::prefix('pipeline')->group(function (): void {
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
        Route::post('{id}/convert', [TendersController::class, 'convertToProject'])
            ->whereNumber('id')
            ->middleware('permission:action.pipeline.update')
            ->name('pipeline.convert');
        Route::delete('{id}', [TendersController::class, 'destroy'])
            ->whereNumber('id')
            ->middleware('permission:action.pipeline.delete')
            ->name('pipeline.destroy');
    });

    Route::prefix('projects')->group(function (): void {
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

        Route::post('{project}/rabs', [RabsController::class, 'storeForProject'])
            ->middleware('permission:action.rabs.create')
            ->name('projects.rabs.store');
        Route::post('{project}/raps', [RapsController::class, 'storeForProject'])
            ->middleware('permission:action.raps.create')
            ->name('projects.raps.store');

        Route::post('{project}/documents', [ProjectDocumentsController::class, 'store'])
            ->middleware('permission:action.projects.update')
            ->name('projects.documents.store');

        Route::prefix('documents')->name('projects.documents.')->group(function (): void {
            Route::post('ocr', [ProjectDocumentsController::class, 'ocr'])
                ->middleware('permission:action.projects.update')
                ->name('ocr');
            Route::post('apply-extraction', [ProjectDocumentsController::class, 'applyExtraction'])
                ->middleware('permission:action.projects.update')
                ->name('apply-extraction');
            Route::post('{projectDocument}/ocr', [ProjectDocumentsController::class, 'ocrStored'])
                ->middleware('permission:action.projects.update')
                ->name('ocr-stored');
            Route::get('{projectDocument}', [ProjectDocumentsController::class, 'show'])
                ->middleware('permission:page.projects.view')
                ->name('show');
            Route::delete('{projectDocument}', [ProjectDocumentsController::class, 'destroy'])
                ->middleware('permission:action.projects.update')
                ->name('destroy');
        });
    });

    Route::prefix('rabs')->group(function (): void {
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

    Route::prefix('rab-items')
        ->name('rabs.items.')
        ->middleware('permission:action.rabs.update')
        ->group(function (): void {
            Route::patch('{id}', [RabItemsController::class, 'update'])
                ->whereNumber('id')
                ->name('update');
            Route::delete('{id}', [RabItemsController::class, 'destroy'])
                ->whereNumber('id')
                ->name('destroy');
        });

    Route::prefix('raps')->group(function (): void {
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

    Route::prefix('rap-items')
        ->name('raps.items.')
        ->middleware('permission:action.raps.update')
        ->group(function (): void {
            Route::patch('{id}', [RapItemsController::class, 'update'])
                ->whereNumber('id')
                ->name('update');
            Route::delete('{id}', [RapItemsController::class, 'destroy'])
                ->whereNumber('id')
                ->name('destroy');
        });

    Route::prefix('progress-updates')->name('progress-updates.')->group(function (): void {
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

    Route::prefix('project-costs')->name('project-costs.')->group(function (): void {
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

    Route::prefix('project-cost-items')
        ->name('project-costs.items.')
        ->middleware('permission:action.project-costs.update')
        ->group(function (): void {
            Route::patch('{id}', [ProjectCostItemsController::class, 'update'])
                ->whereNumber('id')
                ->name('update');
            Route::delete('{id}', [ProjectCostItemsController::class, 'destroy'])
                ->whereNumber('id')
                ->name('destroy');
        });

    Route::get('invoices/{invoice}/preview', [InvoicesController::class, 'preview'])
        ->middleware('permission:page.invoices.view')
        ->name('invoices.preview');

    Route::resource('invoices', InvoicesController::class)
        ->only(['index', 'store', 'show', 'update', 'destroy'])
        ->middlewareFor(['index', 'show'], 'permission:page.invoices.view')
        ->middlewareFor('store', 'permission:action.invoices.create')
        ->middlewareFor('update', 'permission:action.invoices.update')
        ->middlewareFor('destroy', 'permission:action.invoices.delete');

    Route::prefix('invoices/{invoice}/items')
        ->name('invoices.items.')
        ->group(function (): void {
            Route::post('/', [InvoiceItemsController::class, 'store'])
                ->middleware('permission:action.invoices.update')
                ->name('store');
        });

    Route::prefix('invoice-items')
        ->name('invoices.items.')
        ->middleware('permission:action.invoices.update')
        ->group(function (): void {
            Route::patch('{id}', [InvoiceItemsController::class, 'update'])
                ->whereNumber('id')
                ->name('update');
            Route::delete('{id}', [InvoiceItemsController::class, 'destroy'])
                ->whereNumber('id')
                ->name('destroy');
        });

    Route::inertia('billing-test', 'dev/BillingTest')
        ->middleware('permission:page.billing-test.view')
        ->name('billing.test');

    Route::resource('fund-requests', FundRequestsController::class)
        ->middleware('permission:page.fund-requests.view')
        ->only(['index', 'store', 'show', 'update', 'destroy']);

    Route::prefix('ai-document-extraction')->group(function (): void {
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
