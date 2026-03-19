<?php

use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\AccountTypeController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\CustomersController;
use App\Http\Controllers\Admin\FixedAssetController;
use App\Http\Controllers\Admin\ItemCategoryController;
use App\Http\Controllers\Admin\ItemController;
use App\Http\Controllers\Admin\JournalEntryController;
use App\Http\Controllers\Admin\NotificationsController;
use App\Http\Controllers\Admin\PurchaseOrderController;
use App\Http\Controllers\Admin\PurchaseInvoiceController;
use App\Http\Controllers\Admin\PurchaseReturnController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SalesInvoiceController;
use App\Http\Controllers\Admin\SalesMaterialTypesController;
use App\Http\Controllers\Admin\SalesPaymentController;
use App\Http\Controllers\Admin\SalesReturnController;
use App\Http\Controllers\Admin\StockAdjustmentController;
use App\Http\Controllers\Admin\StoresController;
use App\Http\Controllers\Admin\SupplierCategoryController;
use App\Http\Controllers\Admin\SuppliersController;
use App\Http\Controllers\Admin\TreasuriesController;
use App\Http\Controllers\Admin\TreasuryDeliveryController;
use App\Http\Controllers\Admin\UnitController;
use App\Http\Controllers\Admin\UserShiftsController;
use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';

Route::middleware('auth')->group(function () {

    Route::get('/', [AdminController::class, 'index'])->name('admin');
    Route::get('/setting', [AdminController::class, 'setting'])->name('setting');
    Route::post('/setting', [AdminController::class, 'post_setting'])->name('setting.update');
    Route::resource('/treasuries', TreasuriesController::class);
    Route::patch('treasuries/{treasury}/set-master', [TreasuriesController::class, 'setMaster'])->name('treasuries.set-master');

    Route::prefix('treasuries/{treasury}')->group(function () {
        Route::get('deliveries', [TreasuryDeliveryController::class, 'index'])->name('treasuries.deliveries.index');
        Route::get('deliveries/create', [TreasuryDeliveryController::class, 'create'])->name('treasuries.deliveries.create');
        Route::post('deliveries', [TreasuryDeliveryController::class, 'store'])->name('treasuries.deliveries.store');
        Route::get('deliveries/{delivery}', [TreasuryDeliveryController::class, 'show'])->name('treasuries.deliveries.show');
        Route::get('deliveries/{delivery}/edit', [TreasuryDeliveryController::class, 'edit'])->name('treasuries.deliveries.edit');
        Route::put('deliveries/{delivery}', [TreasuryDeliveryController::class, 'update'])->name('treasuries.deliveries.update');
        Route::delete('deliveries/{delivery}', [TreasuryDeliveryController::class, 'destroy'])->name('treasuries.deliveries.destroy');
    });

    Route::resource('sales_material_types', SalesMaterialTypesController::class);
    Route::post('/sales_material_types/toggle-status/{id}', [SalesMaterialTypesController::class, 'toggleStatus'])
        ->name('sales_material_types.toggle-status');

    Route::resource('stores', StoresController::class);
    Route::post('/stores/toggle-status/{id}', [StoresController::class, 'toggleStatus'])
        ->name('stores.toggle-status');

    Route::resource('units', UnitController::class);
    Route::post('/units/toggle-status/{id}', [UnitController::class, 'toggleStatus'])
        ->name('units.toggle-status');

    Route::resource('item_categories', ItemCategoryController::class);
    Route::post('/item_categories/toggle-status/{id}', [ItemCategoryController::class, 'toggleStatus'])
        ->name('item_categories.toggle-status');

    Route::get('/items/select2', [ItemController::class, 'select2'])->name('items.select2');
    Route::resource('items', ItemController::class);
    Route::post('/items/toggle-status/{id}', [ItemController::class, 'toggleStatus'])
        ->name('items.toggle-status');
    Route::post('items/ajax/categories', [ItemController::class, 'ajaxStoreCategory'])
        ->name('items.ajax.categories.store');
    Route::post('items/ajax/parents', [ItemController::class, 'ajaxStoreParent'])
        ->name('items.ajax.parents.store');

    Route::resource('account_types', AccountTypeController::class);
    Route::post('/account_types/toggle-status/{id}', [AccountTypeController::class, 'toggleStatus'])
        ->name('account_types.toggle-status');
    Route::post('account_types/toggle-allow-posting/{id}', [AccountTypeController::class, 'toggleAllowPosting'])
        ->name('account-types.toggle-allow-posting');

    Route::get('accounts/ajax', [AccountController::class, 'ajax'])->name('accounts.ajax');
    Route::resource('accounts', AccountController::class);
    Route::patch('accounts/{account}/toggle-status', [AccountController::class, 'toggleStatus'])->name('accounts.toggle-status');

    Route::get('journal_entries', [JournalEntryController::class, 'index'])->name('journal_entries.index');
    Route::get('journal_entries/create', [JournalEntryController::class, 'create'])->name('journal_entries.create');
    Route::post('journal_entries', [JournalEntryController::class, 'store'])->name('journal_entries.store');
    Route::get('journal_entries/{journalEntry}', [JournalEntryController::class, 'show'])->name('journal_entries.show');

    Route::get('/customers/select2', [CustomersController::class, 'select2'])->name('customers.select2');
    Route::resource('customers', CustomersController::class);
    Route::post('/customers/toggle-status/{id}', [CustomersController::class, 'toggleStatus'])
        ->name('customers.toggle-status');

    Route::resource('supplier_categories', SupplierCategoryController::class);
    Route::post('/supplier_categories/toggle-status/{id}', [SupplierCategoryController::class, 'toggleStatus'])
        ->name('supplier_categories.toggle-status');

    Route::get('suppliers/select2', [SuppliersController::class, 'suppliersSelect2'])->name('suppliers.select2');
    Route::resource('suppliers', SuppliersController::class);
    Route::post('/suppliers/toggle-status/{id}', [SuppliersController::class, 'toggleStatus'])
        ->name('suppliers.toggle-status');

    Route::get('purchase_invoices', [PurchaseInvoiceController::class, 'index'])->name('purchase_invoices.index');
    Route::resource('purchase_orders', PurchaseOrderController::class);
    Route::post('purchase_orders/{purchaseOrder}/convert-to-invoice', [PurchaseOrderController::class, 'convertToInvoice'])
        ->name('purchase_orders.convert_to_invoice');
    Route::resource('purchase_invoices', PurchaseInvoiceController::class);
    Route::post('purchase_invoices/{id}/post', [PurchaseInvoiceController::class, 'post'])->name('purchase_invoices.post');
    Route::post('purchase_invoices/{id}/cancel', [PurchaseInvoiceController::class, 'cancel'])->name('purchase_invoices.cancel');
    Route::get('purchase_invoices/{id}/pdf', [PurchaseInvoiceController::class, 'pdf'])->name('purchase_invoices.pdf');

    Route::get('purchase_returns', [PurchaseReturnController::class, 'index'])->name('purchase_returns.index');
    Route::resource('purchase_returns', PurchaseReturnController::class);
    Route::post('purchase_returns/{id}/post', [PurchaseReturnController::class, 'post'])->name('purchase_returns.post');
    Route::post('purchase_returns/{id}/cancel', [PurchaseReturnController::class, 'cancel'])->name('purchase_returns.cancel');
    Route::get('purchase_returns/{id}/pdf', [PurchaseReturnController::class, 'pdf'])->name('purchase_returns.pdf');
    Route::get('purchase_returns/{invoice}/return', [PurchaseReturnController::class, 'createFromInvoice'])
        ->name('purchase_returns.create_from_invoice');

    Route::get('shifts/close', [UserShiftsController::class, 'closeForm'])->name('shifts.close.form');
    Route::post('shifts/close', [UserShiftsController::class, 'close'])->name('shifts.close');
    Route::resource('shifts', UserShiftsController::class);

    Route::resource('stock_adjustments', StockAdjustmentController::class);
    Route::post('fixed_assets/run-depreciation', [FixedAssetController::class, 'runDepreciation'])->name('fixed_assets.run_depreciation');
    Route::resource('fixed_assets', FixedAssetController::class);

    Route::resource('sales_invoices', SalesInvoiceController::class);
    Route::post('sales_invoices/{id}/post', [SalesInvoiceController::class, 'post'])->name('sales_invoices.post');
    Route::post('sales_invoices/{id}/cancel', [SalesInvoiceController::class, 'cancel'])->name('sales_invoices.cancel');
    Route::get('sales_invoices/{id}/pdf', [SalesInvoiceController::class, 'pdf'])->name('sales_invoices.pdf');
    Route::post('sales_invoices/{invoiceId}/payments', [SalesPaymentController::class, 'store'])->name('sales_payments.store');
    Route::delete('sales_payments/{id}', [SalesPaymentController::class, 'destroy'])->name('sales_payments.destroy');

    Route::get('sales_returns', [SalesReturnController::class, 'index'])->name('sales_returns.index');
    Route::get('sales_returns/create', [SalesReturnController::class, 'create'])->name('sales_returns.create');
    Route::post('sales_returns', [SalesReturnController::class, 'store'])->name('sales_returns.store');
    Route::get('sales_returns/{id}', [SalesReturnController::class, 'show'])->name('sales_returns.show');
    Route::get('sales_returns/{id}/edit', [SalesReturnController::class, 'edit'])->name('sales_returns.edit');
    Route::put('sales_returns/{id}', [SalesReturnController::class, 'update'])->name('sales_returns.update');
    Route::post('sales_returns/{id}/cancel', [SalesReturnController::class, 'cancel'])->name('sales_returns.cancel');
    Route::get('sales_returns/{id}/pdf', [SalesReturnController::class, 'pdf'])->name('sales_returns.pdf');
    Route::get('sales_invoices/{invoiceId}/sales_returns/create', [SalesReturnController::class, 'createFromInvoice'])
        ->name('sales_returns.create_from_invoice');
    Route::post('sales_invoices/{invoiceId}/sales_returns', [SalesReturnController::class, 'storeFromInvoice'])
        ->name('sales_returns.store_from_invoice');

    // ==============================
    // التقارير المالية
    // ==============================
    Route::prefix('reports')->name('reports.')->group(function (): void {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/balance-sheet', [ReportController::class, 'balanceSheet'])->name('balance_sheet');
        Route::get('/income-statement', [ReportController::class, 'incomeStatement'])->name('income_statement');
        Route::get('/trial-balance', [ReportController::class, 'trialBalance'])->name('trial_balance');
        Route::get('/account-statement', [ReportController::class, 'accountStatement'])->name('account_statement');
        Route::get('/cash-flow-statement', [ReportController::class, 'cashFlowStatement'])->name('cash_flow_statement');
    });

    // ==============================
    // الإشعارات
    // ==============================
    Route::prefix('notifications')->name('notifications.')->group(function (): void {
        Route::get('/', [NotificationsController::class, 'index'])->name('index');
        Route::get('/count', [NotificationsController::class, 'unreadCount'])->name('count');
        Route::post('/mark-all-read', [NotificationsController::class, 'markAllRead'])->name('mark_all_read');
        Route::delete('/{id}', [NotificationsController::class, 'destroy'])->name('destroy');
    });

});
