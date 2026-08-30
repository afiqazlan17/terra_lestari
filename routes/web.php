<?php

use App\Http\Controllers\BulkReceiptController;
use App\Http\Controllers\CapitalInjectionController;
use App\Http\Controllers\DailySessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\GoogleDriveAuthController;
use App\Http\Controllers\NbkOrderController;
use App\Http\Controllers\NbkProductController;
use App\Http\Controllers\OrderReceiptController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReceiptExtractionController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StaffController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect(auth()->user()->canManageOperations() ? route('dashboard') : route('pos.index'))
        : redirect()->route('login');
});

Route::middleware(['auth', 'password.change'])->group(function () {
    // Profile (all roles)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Settings (all roles; reset-data is superuser-only, enforced in controller)
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings/locale', [SettingsController::class, 'updateLocale'])->name('settings.locale');
    Route::post('/settings/paper-width', [SettingsController::class, 'updatePaperWidth'])->name('settings.paper-width');
    Route::post('/settings/reset-data', [SettingsController::class, 'resetData'])->name('settings.reset-data');

    // Daily session (all roles can open/close the shop)
    Route::post('/daily-session/open', [DailySessionController::class, 'open'])->name('daily-session.open');
    Route::post('/daily-session/{dailySession}/close', [DailySessionController::class, 'close'])->name('daily-session.close');

    // POS (owner + manager + cashier)
    Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
    Route::post('/pos', [PosController::class, 'store'])->name('pos.store');
    Route::get('/pos/ping', [PosController::class, 'ping'])->name('pos.ping');
    Route::get('/orders/{order}/receipt', [OrderReceiptController::class, 'show'])->name('orders.receipt');
    Route::post('/orders/{order}/void', [PosController::class, 'void'])->name('orders.void');

    // Dashboard, purchases, menu (owner + manager)
    Route::middleware('operations')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Receipt photo -> auto-extract date/amount/supplier (Belian & Perbelanjaan)
        Route::post('/receipts/extract', [ReceiptExtractionController::class, 'extract'])->name('receipts.extract');
        Route::get('/receipts/bulk', [BulkReceiptController::class, 'create'])->name('receipts.bulk.create');
        Route::post('/receipts/bulk', [BulkReceiptController::class, 'store'])->name('receipts.bulk.store');

        // Purchases / beli barang basah (bahan mentah sahaja)
        Route::get('/purchases', [PurchaseController::class, 'index'])->name('purchases.index');
        Route::get('/purchases/create', [PurchaseController::class, 'create'])->name('purchases.create');
        Route::post('/purchases', [PurchaseController::class, 'store'])->name('purchases.store');
        Route::patch('/purchases/{purchase}', [PurchaseController::class, 'update'])->name('purchases.update');
        Route::patch('/purchases/{purchase}/void', [PurchaseController::class, 'void'])->name('purchases.void');

        // Expenses / perbelanjaan (sewa, utiliti, gaji, renovasi, lain-lain)
        Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
        Route::get('/expenses/create', [ExpenseController::class, 'create'])->name('expenses.create');
        Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
        Route::patch('/expenses/{expense}', [ExpenseController::class, 'update'])->name('expenses.update');
        Route::patch('/expenses/{expense}/void', [ExpenseController::class, 'void'])->name('expenses.void');

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

        // NBK vendor order calculator + memo
        Route::get('/nbk/products', [NbkProductController::class, 'index'])->name('nbk.products.index');
        Route::post('/nbk/products', [NbkProductController::class, 'store'])->name('nbk.products.store');
        Route::patch('/nbk/products/{nbkProduct}', [NbkProductController::class, 'update'])->name('nbk.products.update');
        Route::delete('/nbk/products/{nbkProduct}', [NbkProductController::class, 'destroy'])->name('nbk.products.destroy');

        Route::get('/nbk/orders', [NbkOrderController::class, 'index'])->name('nbk.orders.index');
        Route::get('/nbk/orders/create', [NbkOrderController::class, 'create'])->name('nbk.orders.create');
        Route::post('/nbk/orders', [NbkOrderController::class, 'store'])->name('nbk.orders.store');
        Route::get('/nbk/orders/{nbkOrder}', [NbkOrderController::class, 'show'])->name('nbk.orders.show');
        Route::patch('/nbk/orders/{nbkOrder}/paid', [NbkOrderController::class, 'markPaid'])->name('nbk.orders.paid');
    });

    // Staff accounts + capital injections (owner/superuser only)
    Route::middleware('owner')->group(function () {
        Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
        Route::post('/staff', [StaffController::class, 'store'])->name('staff.store');
        Route::patch('/staff/{staffMember}/toggle', [StaffController::class, 'toggle'])->name('staff.toggle');
        Route::patch('/staff/{staffMember}/pin', [StaffController::class, 'updatePin'])->name('staff.pin.update');
        Route::delete('/staff/{staffMember}', [StaffController::class, 'destroy'])->name('staff.destroy');

        Route::post('/capital-injections', [CapitalInjectionController::class, 'store'])->name('capital-injections.store');
        Route::patch('/capital-injections/{capitalInjection}', [CapitalInjectionController::class, 'update'])->name('capital-injections.update');
        Route::patch('/capital-injections/{capitalInjection}/receipt', [CapitalInjectionController::class, 'updateReceipt'])->name('capital-injections.receipt.update');
        Route::delete('/capital-injections/{capitalInjection}', [CapitalInjectionController::class, 'destroy'])->name('capital-injections.destroy');

        // One-time Google Drive OAuth authorisation for the nightly receipt backup
        Route::get('/google-drive/authorize', [GoogleDriveAuthController::class, 'authorize'])->name('google-drive.authorize');
        Route::get('/google-drive/callback', [GoogleDriveAuthController::class, 'callback'])->name('google-drive.callback');
    });
});

require __DIR__.'/auth.php';
