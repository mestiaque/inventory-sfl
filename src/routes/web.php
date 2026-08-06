<?php

use Illuminate\Support\Facades\Route;
use ME\SflInventory\Http\Controllers\DashboardController;
use ME\SflInventory\Http\Controllers\InvBrandController;
use ME\SflInventory\Http\Controllers\InvBuyerController;
use ME\SflInventory\Http\Controllers\InvColorController;
use ME\SflInventory\Http\Controllers\InvDepartmentController;
use ME\SflInventory\Http\Controllers\InvFinishedGoodsReceiveController;
use ME\SflInventory\Http\Controllers\InvGatePassController;
use ME\SflInventory\Http\Controllers\InvGrnController;
use ME\SflInventory\Http\Controllers\InvGuidelineController;
use ME\SflInventory\Http\Controllers\InvIssueController;
use ME\SflInventory\Http\Controllers\InvItemCategoryController;
use ME\SflInventory\Http\Controllers\InvItemController;
use ME\SflInventory\Http\Controllers\InvOperatorController;
use ME\SflInventory\Http\Controllers\InvProductionConsumptionController;
use ME\SflInventory\Http\Controllers\InvPurchaseOrderController;
use ME\SflInventory\Http\Controllers\InvReportController;
use ME\SflInventory\Http\Controllers\InvRequisitionController;
use ME\SflInventory\Http\Controllers\InvShipmentController;
use ME\SflInventory\Http\Controllers\InvSignatureController;
use ME\SflInventory\Http\Controllers\InvSizeController;
use ME\SflInventory\Http\Controllers\InvStockAdjustmentController;
use ME\SflInventory\Http\Controllers\InvStockLedgerController;
use ME\SflInventory\Http\Controllers\InvStockOverviewController;
use ME\SflInventory\Http\Controllers\InvStockTransferController;
use ME\SflInventory\Http\Controllers\InvStoreController;
use ME\SflInventory\Http\Controllers\InvSupplierController;
use ME\SflInventory\Http\Controllers\InvUnitController;

$route = config('sfl-inventory.route');

Route::middleware($route['middleware'] ?? ['web', 'auth'])
    ->prefix($route['prefix'] ?? 'admin/inventory')
    ->name($route['as'] ?? 'inventory.')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('guideline', [InvGuidelineController::class, 'index'])->name('guideline');
        Route::get('my-signature', [InvSignatureController::class, 'edit'])->name('signature.edit');
        Route::post('my-signature', [InvSignatureController::class, 'update'])->name('signature.update');
        Route::delete('my-signature', [InvSignatureController::class, 'destroy'])->name('signature.destroy');

        // Masters
        Route::resource('stores', InvStoreController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('item-categories', InvItemCategoryController::class)->only(['index', 'store', 'update', 'destroy'])
            ->parameters(['item-categories' => 'item_category']);
        Route::resource('units', InvUnitController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('brands', InvBrandController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('colors', InvColorController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('sizes', InvSizeController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('suppliers', InvSupplierController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('buyers', InvBuyerController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('departments', InvDepartmentController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('operators', InvOperatorController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('items', InvItemController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);

        // Purchase -> GRN
        Route::resource('purchase-orders', InvPurchaseOrderController::class)
            ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'])
            ->parameters(['purchase-orders' => 'purchase_order']);
        Route::post('purchase-orders/{purchase_order}/approve', [InvPurchaseOrderController::class, 'approve'])
            ->name('purchase-orders.approve');
        Route::resource('grns', InvGrnController::class)->only(['index', 'create', 'store', 'destroy']);
        Route::get('grns/create/purchase', [InvGrnController::class, 'createPurchase'])->name('grns.create-purchase');
        Route::get('grns/create/buyer', [InvGrnController::class, 'createBuyer'])->name('grns.create-buyer');

        // Requisition -> Approval -> Issue -> Department Receive
        Route::resource('requisitions', InvRequisitionController::class)
            ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
        Route::get('requisitions/{requisition}/approval', [InvRequisitionController::class, 'approvalForm'])
            ->name('requisitions.approval-form');
        Route::post('requisitions/{requisition}/approval', [InvRequisitionController::class, 'approval'])
            ->name('requisitions.approval');
        Route::get('requisitions/{requisition}/print', [InvRequisitionController::class, 'print'])
            ->name('requisitions.print');
        Route::resource('issues', InvIssueController::class)->only(['index', 'create', 'store']);
        Route::post('issues/{issue}/authorize', [InvIssueController::class, 'authorizeIssue'])->name('issues.authorize');
        Route::post('issues/{issue}/approve', [InvIssueController::class, 'approve'])->name('issues.approve');
        Route::delete('issues/{issue}/cancel', [InvIssueController::class, 'cancel'])->name('issues.cancel');
        Route::get('issues/{issue}/print', [InvIssueController::class, 'print'])->name('issues.print');
        Route::get('issues/{issue}/receive', [InvIssueController::class, 'receiveForm'])->name('issues.receive-form');
        Route::post('issues/{issue}/receive', [InvIssueController::class, 'receive'])->name('issues.receive');

        // Internal Stock Transfer + Production Consumption
        Route::resource('transfers', InvStockTransferController::class)->only(['index', 'create', 'store']);
        Route::post('transfers/{transfer}/approve', [InvStockTransferController::class, 'approve'])->name('transfers.approve');
        Route::post('transfers/{transfer}/reject', [InvStockTransferController::class, 'reject'])->name('transfers.reject');
        Route::get('transfers/{transfer}/receive', [InvStockTransferController::class, 'receiveForm'])->name('transfers.receive-form');
        Route::post('transfers/{transfer}/receive', [InvStockTransferController::class, 'receive'])->name('transfers.receive');
        Route::resource('production-consumptions', InvProductionConsumptionController::class)->only(['index', 'create', 'store']);

        // Finished Goods -> Gate Pass -> Shipment
        Route::resource('fg-receives', InvFinishedGoodsReceiveController::class)->only(['index', 'create', 'store']);
        Route::resource('gate-passes', InvGatePassController::class)
            ->only(['index', 'create', 'store'])
            ->parameters(['gate-passes' => 'gate_pass']);
        Route::post('gate-passes/{gate_pass}/approve', [InvGatePassController::class, 'approve'])->name('gate-passes.approve');
        Route::resource('shipments', InvShipmentController::class)->only(['index', 'create', 'store']);
        Route::post('shipments/{shipment}/status', [InvShipmentController::class, 'updateStatus'])->name('shipments.status');

        // Stock Adjustment
        Route::resource('adjustments', InvStockAdjustmentController::class)->only(['index', 'create', 'store']);
        Route::post('adjustments/{adjustment}/approve', [InvStockAdjustmentController::class, 'approve'])->name('adjustments.approve');

        // Stock Ledger + Main Store Inventory
        Route::get('stock-ledger', [InvStockLedgerController::class, 'index'])->name('stock-ledger.index');
        Route::get('stock-overview', [InvStockOverviewController::class, 'index'])->name('stock-overview.index');
        Route::get('store-overview', [InvStockOverviewController::class, 'cards'])->name('stock-overview.cards');

        // Reports
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('{report}/export', [InvReportController::class, 'export'])->name('export');
            Route::get('current-stock', [InvReportController::class, 'currentStock'])->name('current-stock');
            Route::get('stock-summary', [InvReportController::class, 'stockSummary'])->name('stock-summary');
            Route::get('item-history', [InvReportController::class, 'itemHistory'])->name('item-history');
            Route::get('store-wise-stock', [InvReportController::class, 'storeWiseStock'])->name('store-wise-stock');
            Route::get('department-consumption', [InvReportController::class, 'departmentWiseConsumption'])->name('department-consumption');
            Route::get('supplier-purchase', [InvReportController::class, 'supplierWisePurchase'])->name('supplier-purchase');
            Route::get('grn', [InvReportController::class, 'grnReport'])->name('grn');
            Route::get('issue', [InvReportController::class, 'issueReport'])->name('issue');
            Route::get('gate-pass', [InvReportController::class, 'gatePassReport'])->name('gate-pass');
            Route::get('shipment', [InvReportController::class, 'shipmentReport'])->name('shipment');
            Route::get('low-stock', [InvReportController::class, 'lowStock'])->name('low-stock');
            Route::get('dead-stock', [InvReportController::class, 'deadStock'])->name('dead-stock');
            Route::get('stock-valuation', [InvReportController::class, 'stockValuation'])->name('stock-valuation');
            Route::get('store-inventory-report', [InvReportController::class, 'storeInventoryReport'])->name('store-inventory-report');
        });
    });
