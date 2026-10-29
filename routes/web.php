<?php
use App\Http\Controllers\{AuthController, DashboardController, SaleController, ProductController, InventoryController,
    SupplierController, PurchaseController, ReportController, UserController, UtilityController};
use App\Http\Middleware\EnsureRole;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check() ? redirect()->route('overview') : redirect()->route('login'))->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'show'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
});
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::get('/profile/password', [\App\Http\Controllers\PasswordController::class, 'edit'])->name('password.edit');
    Route::post('/profile/password', [\App\Http\Controllers\PasswordController::class, 'update'])->name('password.update')->middleware('throttle:6,1');
    Route::get('/overview', [DashboardController::class, 'index'])->name('overview');
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');

    // Owner + Cashier
    Route::middleware(EnsureRole::class . ':owner,cashier')->group(function () {
        Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
        Route::post('/sales', [SaleController::class, 'store'])->name('sales.store');
        Route::get('/sales/{sale}/receipt', [SaleController::class, 'receipt'])->name('sales.receipt');
    });

    // Owner + Stock personnel
    Route::middleware(EnsureRole::class . ':owner,stock')->group(function () {
        Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::get('/inventory/report', [InventoryController::class, 'report'])->name('inventory.report');
        Route::get('/inventory/export', [InventoryController::class, 'export'])->name('inventory.export');
        Route::post('/inventory/counts', [InventoryController::class, 'count'])->name('inventory.count');
        Route::get('/suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
        Route::get('/suppliers/{supplier}', [SupplierController::class, 'show'])->name('suppliers.show');
        Route::get('/purchases', [PurchaseController::class, 'index'])->name('purchases.index');
        Route::post('/purchases/{purchase}/receive', [PurchaseController::class, 'receive'])->name('purchases.receive');
    });

    // Owner / Manager only
    Route::middleware(EnsureRole::class . ':owner')->group(function () {
        Route::post('/sales/{sale}/void', [SaleController::class, 'void'])->name('sales.void');

        Route::get('/products/export', [ProductController::class, 'export'])->name('products.export');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::post('/products/{product}/archive', [ProductController::class, 'archive'])->name('products.archive');

        Route::post('/inventory/counts/{adjustment}/approve', [InventoryController::class, 'approve'])->name('inventory.approve');
        Route::post('/inventory/counts/{adjustment}/reject', [InventoryController::class, 'reject'])->name('inventory.reject');

        Route::post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
        Route::put('/suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
        Route::post('/purchases', [PurchaseController::class, 'store'])->name('purchases.store');

        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');

        Route::get('/user-access', [UserController::class, 'index'])->name('users.index');
        Route::post('/user-access', [UserController::class, 'store'])->name('users.store');
        Route::put('/user-access/{user}', [UserController::class, 'update'])->name('users.update');

        Route::get('/utilities', [UtilityController::class, 'index'])->name('utilities.index');
        Route::get('/utilities/backup', [UtilityController::class, 'backup'])->name('utilities.backup');
        Route::post('/utilities/restore/preview', [UtilityController::class, 'restorePreview'])->name('utilities.restore.preview');
        Route::post('/utilities/restore', [UtilityController::class, 'restore'])->name('utilities.restore');
        Route::post('/sample-data', [UtilityController::class, 'sample'])->name('sample.load');
    });
});
