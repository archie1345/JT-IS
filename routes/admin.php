<?php

use App\Http\Controllers\Admin\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:admin', 'permission:page.admin.accounts.view'])
    ->prefix('Admin_acc_mgmt')
    ->group(function (): void {
        Route::get('/', [UserManagementController::class, 'index'])
            ->name('admin.acc_mgmt');
        Route::post('/', [UserManagementController::class, 'store'])
            ->middleware('permission:action.admin.accounts.create')
            ->name('admin.acc_mgmt.store');
        Route::patch('{user}', [UserManagementController::class, 'update'])
            ->middleware('permission:action.admin.accounts.update')
            ->name('admin.acc_mgmt.update');
        Route::delete('{user}', [UserManagementController::class, 'destroy'])
            ->middleware('permission:action.admin.accounts.delete')
            ->name('admin.acc_mgmt.destroy');
        Route::patch('roles/{role}', [UserManagementController::class, 'updateRolePermissions'])
            ->whereNumber('role')
            ->middleware('permission:action.admin.roles.manage')
            ->name('admin.acc_mgmt.roles.update');
    });
