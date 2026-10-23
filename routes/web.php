<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BorrowingController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ComponentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\KitController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StudentPortalController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'create'])->name('login');
Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:6,1')->name('login.store');
Route::post('/logout', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/student-portal', StudentPortalController::class)
        ->middleware('role:administrator,inventory_manager,viewer')
        ->name('student-portal');

    Route::get('/components', [ComponentController::class, 'index'])->name('components.index');
    Route::post('/categories', [CategoryController::class, 'store'])->middleware('role:administrator,inventory_manager,coach')->name('categories.store');
    Route::get('/components/create', [ComponentController::class, 'create'])->middleware('role:administrator,inventory_manager,coach')->name('components.create');
    Route::post('/components', [ComponentController::class, 'store'])->middleware('role:administrator,inventory_manager,coach')->name('components.store');
    Route::get('/components/{component}', [ComponentController::class, 'show'])->name('components.show');
    Route::get('/components/{component}/edit', [ComponentController::class, 'edit'])->middleware('role:administrator,inventory_manager,coach')->name('components.edit');
    Route::put('/components/{component}', [ComponentController::class, 'update'])->middleware('role:administrator,inventory_manager,coach')->name('components.update');
    Route::delete('/components/{component}', [ComponentController::class, 'destroy'])->middleware('role:administrator')->name('components.destroy');

    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::post('/inventory/movements', [InventoryController::class, 'store'])->middleware('role:administrator,inventory_manager,coach')->name('inventory.store');
    Route::get('/imports', [ImportController::class, 'index'])->middleware('role:administrator,inventory_manager,coach')->name('imports.index');
    Route::post('/imports/preview', [ImportController::class, 'preview'])->middleware('role:administrator,inventory_manager,coach')->name('imports.preview');
    Route::get('/imports/review', [ImportController::class, 'review'])->middleware('role:administrator,inventory_manager,coach')->name('imports.review');
    Route::post('/imports/commit', [ImportController::class, 'commit'])->middleware('role:administrator,inventory_manager,coach')->name('imports.commit');

    Route::get('/borrowings', [BorrowingController::class, 'index'])->name('borrowings.index');
    Route::get('/borrowings/create', [BorrowingController::class, 'create'])->middleware('role:administrator,inventory_manager,coach')->name('borrowings.create');
    Route::post('/borrowings', [BorrowingController::class, 'store'])->middleware('role:administrator,inventory_manager,coach')->name('borrowings.store');
    Route::post('/borrowings/{borrowing}/items/{item}/return', [BorrowingController::class, 'return'])->middleware('role:administrator,inventory_manager,coach')->name('borrowings.return');

    Route::get('/kits', [KitController::class, 'index'])->name('kits.index');
    Route::post('/kits', [KitController::class, 'store'])->middleware('role:administrator,inventory_manager,coach')->name('kits.store');
    Route::put('/kits/{kit}/components', [KitController::class, 'syncComponents'])->middleware('role:administrator,inventory_manager,coach')->name('kits.components.sync');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');

    Route::get('/users', [UserController::class, 'index'])->middleware('role:administrator')->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->middleware('role:administrator')->name('users.store');
    Route::patch('/users/{user}/role', [UserController::class, 'updateRole'])->middleware('role:administrator')->name('users.role');
    Route::put('/users/{user}/password', [UserController::class, 'resetPassword'])->middleware('role:administrator')->name('users.password');
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
});
