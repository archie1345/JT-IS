<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DashboardLayoutController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('dashboard', DashboardController::class)
        ->middleware('permission:page.dashboard.view')
        ->name('dashboard');

    Route::post('dashboard/layout', [DashboardLayoutController::class, 'store'])
        ->name('dashboard.layout.store');
});

require __DIR__.'/project-monitoring.php';
require __DIR__.'/admin.php';
require __DIR__.'/settings.php';
