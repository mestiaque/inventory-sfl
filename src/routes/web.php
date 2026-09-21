<?php

use Illuminate\Support\Facades\Route;
use ME\SflInventory\Http\Controllers\DashboardController;
use ME\SflInventory\Http\Controllers\InvBrandController;
use ME\SflInventory\Http\Controllers\InvBrokenNeedleController;
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
use ME\SflInventory\Http\Controllers\InvMachineController;
use ME\SflInventory\Http\Controllers\InvOperatorController;
use ME\SflInventory\Http\Controllers\InvProductionConsumptionController;
use ME\SflInventory\Http\Controllers\InvPurchaseOrderController;
use ME\SflInventory\Http\Controllers\InvPurchaseRequisitionController;
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
        Route::post('stores/{store}/restore', [InvStoreController::class, 'restore'])->name('stores.restore')->withTrashed();
        Route::delete('stores/{store}/force', [InvStoreController::class, 'forceDestroy'])->name('stores.force-destroy')->withTrashed();
        Route::resource('item-categories', InvItemCategoryController::class)->only(['index', 'store', 'update', 'destroy'])
            ->parameters(['item-categories' => 'item_category']);
        Route::post('item-categories/{item_category}/restore', [InvItemCategoryController::class, 'restore'])->name('item-categories.restore')->withTrashed();
        Route::delete('item-categories/{item_category}/force', [InvItemCategoryController::class, 'forceDestroy'])->name('item-categories.force-destroy')->withTrashed();
        Route::resource('units', InvUnitController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::post('units/{unit}/restore', [InvUnitController::class, 'restore'])->name('units.restore')->withTrashed();
        Route::delete('units/{unit}/force', [InvUnitController::class, 'forceDestroy'])->name('units.force-destroy')->withTrashed();
        Route::resource('brands', InvBrandController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::post('brands/{brand}/restore', [InvBrandController::class, 'restore'])->name('brands.restore')->withTrashed();
        Route::delete('brands/{brand}/force', [InvBrandController::class, 'forceDestroy'])->name('brands.force-destroy')->withTrashed();
        Route::resource('colors', InvColorController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::post('colors/{color}/restore', [InvColorController::class, 'restore'])->name('colors.restore')->withTrashed();
        Route::delete('colors/{color}/force', [InvColorController::class, 'forceDestroy'])->name('colors.force-destroy')->withTrashed();
        Route::resource('sizes', InvSizeController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::post('sizes/{size}/restore', [InvSizeController::class, 'restore'])->name('sizes.restore')->withTrashed();
        Route::delete('sizes/{size}/force', [InvSizeController::class, 'forceDestroy'])->name('sizes.force-destroy')->withTrashed();
        Route::resource('suppliers', InvSupplierController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::post('suppliers/{supplier}/restore', [InvSupplierController::class, 'restore'])->name('suppliers.restore')->withTrashed();
        Route::delete('suppliers/{supplier}/force', [InvSupplierController::class, 'forceDestroy'])->name('suppliers.force-destroy')->withTrashed();
        Route::resource('buyers', InvBuyerController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::post('buyers/{buyer}/restore', [InvBuyerController::class, 'restore'])->name('buyers.restore')->withTrashed();
        Route::delete('buyers/{buyer}/force', [InvBuyerController::class, 'forceDestroy'])->name('buyers.force-destroy')->withTrashed();
        Route::resource('departments', InvDepartmentController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::post('departments/{department}/restore', [InvDepartmentController::class, 'restore'])->name('departments.restore')->withTrashed();
        Route::delete('departments/{department}/force', [InvDepartmentController::class, 'forceDestroy'])->name('departments.force-destroy')->withTrashed();
        Route::resource('operators', InvOperatorController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::post('operators/{operator}/restore', [InvOperatorController::class, 'restore'])->name('operators.restore')->withTrashed();
        Route::delete('operators/{operator}/force', [InvOperatorController::class, 'forceDestroy'])->name('operators.force-destroy')->withTrashed();
        Route::resource('machines', InvMachineController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::post('machines/{machine}/restore', [InvMachineController::class, 'restore'])->name('machines.restore')->withTrashed();
        Route::delete('machines/{machine}/force', [InvMachineController::class, 'forceDestroy'])->name('machines.force-destroy')->withTrashed();
        Route::resource('items', InvItemController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
        Route::post('items/{item}/restore', [InvItemController::class, 'restore'])->name('items.restore')->withTrashed();
        Route::delete('items/{item}/force', [InvItemController::class, 'forceDestroy'])->name('items.force-destroy')->withTrashed();
        Route::post('items/{item}/generate-barcode', [InvItemController::class, 'generateBarcode'])->name('items.generate-barcode');

        // Purchase Requisition -> Approval -> Purchase Order -> GRN
        Route::resource('purchase-requisitions', InvPurchaseRequisitionController::class)
            ->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])
            ->parameters(['purchase-requisitions' => 'purchase_requisition']);
        Route::post('purchase-requisitions/{purchase_requisition}/restore', [InvPurchaseRequisitionController::class, 'restore'])->name('purchase-requisitions.restore')->withTrashed();
        Route::delete('purchase-requisitions/{purchase_requisition}/force', [InvPurchaseRequisitionController::class, 'forceDestroy'])->name('purchase-requisitions.force-destroy')->withTrashed();
        Route::get('purchase-requisitions/{purchase_requisition}/approval', [InvPurchaseRequisitionController::class, 'approvalForm'])
            ->name('purchase-requisitions.approval-form');
        Route::post('purchase-requisitions/{purchase_requisition}/approval', [InvPurchaseRequisitionController::class, 'approval'])
            ->name('purchase-requisitions.approval');

        Route::resource('purchase-orders', InvPurchaseOrderController::class)
            ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'])
            ->parameters(['purchase-orders' => 'purchase_order']);
        Route::post('purchase-orders/{purchase_order}/approve', [InvPurchaseOrderController::class, 'approve'])
            ->name('purchase-orders.approve');
        Route::post('purchase-orders/{purchase_order}/restore', [InvPurchaseOrderController::class, 'restore'])->name('purchase-orders.restore')->withTrashed();
        Route::delete('purchase-orders/{purchase_order}/force', [InvPurchaseOrderController::class, 'forceDestroy'])->name('purchase-orders.force-destroy')->withTrashed();
        Route::resource('grns', InvGrnController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
        Route::get('grns/create/purchase', [InvGrnController::class, 'createPurchase'])->name('grns.create-purchase');
        Route::get('grns/create/buyer', [InvGrnController::class, 'createBuyer'])->name('grns.create-buyer');
        Route::get('grns/{grn}/approval', [InvGrnController::class, 'approvalForm'])->name('grns.approval-form');
        Route::post('grns/{grn}/approval', [InvGrnController::class, 'approval'])->name('grns.approval');
        Route::post('grns/{grn}/restore', [InvGrnController::class, 'restore'])->name('grns.restore')->withTrashed();
        Route::delete('grns/{grn}/force', [InvGrnController::class, 'forceDestroy'])->name('grns.force-destroy')->withTrashed();

        // Requisition -> Approval -> Issue -> Department Receive
        Route::resource('requisitions', InvRequisitionController::class)
            ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
        Route::post('requisitions/{requisition}/restore', [InvRequisitionController::class, 'restore'])->name('requisitions.restore')->withTrashed();
        Route::delete('requisitions/{requisition}/force', [InvRequisitionController::class, 'forceDestroy'])->name('requisitions.force-destroy')->withTrashed();
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
        Route::resource('transfers', InvStockTransferController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
        Route::post('transfers/{transfer}/approve', [InvStockTransferController::class, 'approve'])->name('transfers.approve');
        Route::post('transfers/{transfer}/reject', [InvStockTransferController::class, 'reject'])->name('transfers.reject');
        Route::get('transfers/{transfer}/receive', [InvStockTransferController::class, 'receiveForm'])->name('transfers.receive-form');
        Route::post('transfers/{transfer}/receive', [InvStockTransferController::class, 'receive'])->name('transfers.receive');
        Route::post('transfers/{transfer}/restore', [InvStockTransferController::class, 'restore'])->name('transfers.restore')->withTrashed();
        Route::delete('transfers/{transfer}/force', [InvStockTransferController::class, 'forceDestroy'])->name('transfers.force-destroy')->withTrashed();
        Route::resource('production-consumptions', InvProductionConsumptionController::class)->only(['index', 'create', 'store', 'destroy'])
            ->parameters(['production-consumptions' => 'production_consumption']);
        Route::post('production-consumptions/{production_consumption}/restore', [InvProductionConsumptionController::class, 'restore'])->name('production-consumptions.restore')->withTrashed();
        Route::delete('production-consumptions/{production_consumption}/force', [InvProductionConsumptionController::class, 'forceDestroy'])->name('production-consumptions.force-destroy')->withTrashed();

        // Finished Goods -> Gate Pass -> Shipment
        Route::resource('fg-receives', InvFinishedGoodsReceiveController::class)->only(['index', 'create', 'store']);
        Route::resource('gate-passes', InvGatePassController::class)
            ->only(['index', 'create', 'store'])
            ->parameters(['gate-passes' => 'gate_pass']);
        Route::post('gate-passes/{gate_pass}/approve', [InvGatePassController::class, 'approve'])->name('gate-passes.approve');
        Route::resource('shipments', InvShipmentController::class)->only(['index', 'create', 'store']);
        Route::post('shipments/{shipment}/status', [InvShipmentController::class, 'updateStatus'])->name('shipments.status');

        // Stock Adjustment
        Route::resource('adjustments', InvStockAdjustmentController::class)->only(['index', 'create', 'store', 'destroy']);
        Route::post('adjustments/{adjustment}/approve', [InvStockAdjustmentController::class, 'approve'])->name('adjustments.approve');
        Route::post('adjustments/{adjustment}/reject', [InvStockAdjustmentController::class, 'reject'])->name('adjustments.reject');
        Route::post('adjustments/{adjustment}/restore', [InvStockAdjustmentController::class, 'restore'])->name('adjustments.restore')->withTrashed();
        Route::delete('adjustments/{adjustment}/force', [InvStockAdjustmentController::class, 'forceDestroy'])->name('adjustments.force-destroy')->withTrashed();

        // Stock Ledger + Main Store Inventory
        Route::get('stock-ledger', [InvStockLedgerController::class, 'index'])->name('stock-ledger.index');
        Route::get('stock-overview', [InvStockOverviewController::class, 'index'])->name('stock-overview.index');
        Route::get('store-overview', [InvStockOverviewController::class, 'cards'])->name('stock-overview.cards');

        // Broken Needle tracking
        Route::get('broken-needles/report', [InvBrokenNeedleController::class, 'report'])->name('broken-needles.report');
        Route::get('broken-needles/report/export', [InvBrokenNeedleController::class, 'exportCombinedReport'])->name('broken-needles.report.export');
        Route::get('broken-needles/machine-report', [InvBrokenNeedleController::class, 'machineReport'])->name('broken-needles.machine-report');
        Route::get('broken-needles/machine-report/export', [InvBrokenNeedleController::class, 'exportCombinedReport'])->name('broken-needles.machine-report.export');
        Route::get('broken-needles/daily-report', [InvBrokenNeedleController::class, 'dailyReport'])->name('broken-needles.daily-report');
        Route::get('broken-needles/daily-report/export', [InvBrokenNeedleController::class, 'exportDailyReport'])->name('broken-needles.daily-report.export');
        Route::resource('broken-needles', InvBrokenNeedleController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->parameters(['broken-needles' => 'broken_needle']);
        Route::post('broken-needles/{broken_needle}/restore', [InvBrokenNeedleController::class, 'restore'])->name('broken-needles.restore')->withTrashed();
        Route::delete('broken-needles/{broken_needle}/force', [InvBrokenNeedleController::class, 'forceDestroy'])->name('broken-needles.force-destroy')->withTrashed();

        // Reports
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('{report}/export', [InvReportController::class, 'export'])->name('export');
            Route::get('current-stock', [InvReportController::class, 'currentStock'])->name('current-stock');
            Route::get('stock-summary', [InvReportController::class, 'stockSummary'])->name('stock-summary');
            Route::get('item-history', [InvReportController::class, 'itemHistory'])->name('item-history');
            Route::get('item-full-trail', [InvReportController::class, 'itemFullTrail'])->name('item-full-trail');
            Route::get('store-wise-stock', [InvReportController::class, 'storeWiseStock'])->name('store-wise-stock');
            Route::get('department-consumption', [InvReportController::class, 'departmentWiseConsumption'])->name('department-consumption');
            Route::get('supplier-purchase', [InvReportController::class, 'supplierWisePurchase'])->name('supplier-purchase');
            Route::get('supplier-list', [InvReportController::class, 'supplierList'])->name('supplier-list');
            Route::get('buyer-wise', [InvReportController::class, 'buyerWiseReport'])->name('buyer-wise');
            Route::get('style-wise', [InvReportController::class, 'styleWiseReport'])->name('style-wise');
            Route::get('grn', [InvReportController::class, 'grnReport'])->name('grn');
            Route::get('grn-item-wise', [InvReportController::class, 'grnItemWiseReport'])->name('grn-item-wise');
            Route::get('expiry-tracking', [InvReportController::class, 'expiryTracking'])->name('expiry-tracking');
            Route::get('issue', [InvReportController::class, 'issueReport'])->name('issue');
            Route::get('gate-pass', [InvReportController::class, 'gatePassReport'])->name('gate-pass');
            Route::get('shipment', [InvReportController::class, 'shipmentReport'])->name('shipment');
            Route::get('low-stock', [InvReportController::class, 'lowStock'])->name('low-stock');
            Route::get('dead-stock', [InvReportController::class, 'deadStock'])->name('dead-stock');
            Route::get('stock-valuation', [InvReportController::class, 'stockValuation'])->name('stock-valuation');
            Route::get('store-inventory-report', [InvReportController::class, 'storeInventoryReport'])->name('store-inventory-report');
        });
    });
