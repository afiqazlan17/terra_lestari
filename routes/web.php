<?php

use App\Http\Controllers\CapitalInjectionController;
use App\Http\Controllers\DailySessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\OrderReceiptController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StaffController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect(auth()->user()->canManageOperations() ? route('dashboard') : route('pos.index'))
        : redirect()->route('login');
});

Route::middleware(['auth'])->group(function () {
    // Profile (all roles)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Settings (all roles; reset-data is superuser-only, enforced in controller)
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings/locale', [SettingsController::class, 'updateLocale'])->name('settings.locale');
    Route::post('/settings/reset-data', [SettingsController::class, 'resetData'])->name('settings.reset-data');

    // Daily session (all roles can open/close the shop)
    Route::post('/daily-session/open', [DailySessionController::class, 'open'])->name('daily-session.open');
    Route::post('/daily-session/{dailySession}/close', [DailySessionController::class, 'close'])->name('daily-session.close');

    // POS (owner + manager + cashier)
    Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
    Route::post('/pos', [PosController::class, 'store'])->name('pos.store');
    Route::get('/orders/{order}/receipt', [OrderReceiptController::class, 'show'])->name('orders.receipt');

    // Dashboard, purchases, menu (owner + manager)
    Route::middleware('operations')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Purchases / beli barang basah (bahan mentah sahaja)
        Route::get('/purchases', [PurchaseController::class, 'index'])->name('purchases.index');
        Route::get('/purchases/create', [PurchaseController::class, 'create'])->name('purchases.create');
        Route::post('/purchases', [PurchaseController::class, 'store'])->name('purchases.store');
        Route::delete('/purchases/{purchase}', [PurchaseController::class, 'destroy'])->name('purchases.destroy');

        // Expenses / perbelanjaan (sewa, utiliti, gaji, renovasi, lain-lain)
        Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
        Route::get('/expenses/create', [ExpenseController::class, 'create'])->name('expenses.create');
        Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
        Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');

        // Finance
        Route::get('/finance', [FinanceController::class, 'index'])->name('finance.index');
        Route::get('/finance/sales', [FinanceController::class, 'sales'])->name('finance.sales');
        Route::get('/finance/sales/export', [FinanceController::class, 'exportSales'])->name('finance.sales.export');
        Route::get('/finance/cashbook', [FinanceController::class, 'cashbook'])->name('finance.cashbook');
        Route::get('/finance/cashbook/export', [FinanceController::class, 'exportCashbook'])->name('finance.cashbook.export');

        // Menu / products
        Route::get('/products', [ProductController::class, 'index'])->name('products.index');
        Route::post('/products/categories', [ProductController::class, 'storeCategory'])->name('products.categories.store');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::patch('/products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
    });

    // Staff accounts + capital injections (owner/superuser only)
    Route::middleware('owner')->group(function () {
        Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
        Route::post('/staff', [StaffController::class, 'store'])->name('staff.store');
        Route::patch('/staff/{staffMember}/toggle', [StaffController::class, 'toggle'])->name('staff.toggle');
        Route::delete('/staff/{staffMember}', [StaffController::class, 'destroy'])->name('staff.destroy');

        Route::post('/capital-injections', [CapitalInjectionController::class, 'store'])->name('capital-injections.store');
        Route::patch('/capital-injections/{capitalInjection}/receipt', [CapitalInjectionController::class, 'updateReceipt'])->name('capital-injections.receipt.update');
        Route::delete('/capital-injections/{capitalInjection}', [CapitalInjectionController::class, 'destroy'])->name('capital-injections.destroy');
    });
});

require __DIR__.'/auth.php';
