<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\ActivityController;
use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Applications\LoanApplicationController;

Route::middleware(['auth', 'role:admin,super-admin', 'audit.admin'])
    ->prefix('admin')
    ->group(function () {

        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/export', [DashboardController::class, 'export'])->name('dashboard.export');

        // Settings landing page
        Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');

        Route::middleware('permission:branches.manage')->group(function () {
            Route::get('branches', [BranchController::class, 'index'])->name('branches.index');
            Route::post('branches', [BranchController::class, 'store'])->name('branches.store');
            Route::put('branches/{branch}', [BranchController::class, 'update'])->name('branches.update');
            Route::patch('branches/{branch}/toggle-active', [BranchController::class, 'toggleActive'])->name('branches.toggle-active');
        });

        // Loan Applications
        Route::get('loan-applications', [LoanApplicationController::class, 'index'])->name('loan-applications.index');
        Route::get('loan-applications/create', [LoanApplicationController::class, 'create'])->name('loan-applications.create');

        // Activity log
        Route::get('activities', [ActivityController::class, 'index'])->name('activities.index');

        // Users
        Route::resource('users', UserController::class)->except(['show']);
        Route::patch('users/{user}/toggle-active', [UserController::class, 'toggleActive'])
            ->name('users.toggle-active');

        // Roles
        Route::resource('roles', RoleController::class)->except(['show']);

        // Permissions
        Route::get('permissions', [PermissionController::class, 'index'])->name('permissions.index');
        Route::post('permissions', [PermissionController::class, 'store'])->name('permissions.store');
        Route::put('permissions/{permission}', [PermissionController::class, 'update'])->name('permissions.update');
        Route::patch('permissions/{permission}/toggle-active', [PermissionController::class, 'toggleActive'])->name('permissions.toggle-active');
        Route::delete('permissions/{permission}', [PermissionController::class, 'destroy'])->name('permissions.destroy');

        // Profile
        Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    });
