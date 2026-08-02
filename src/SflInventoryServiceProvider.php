<?php

namespace ME\SflInventory;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use ME\SflInventory\Services\DocumentNumberService;
use ME\SflInventory\Services\InvOperatorScopeService;
use ME\SflInventory\Services\StockService;

class SflInventoryServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'sfl-inventory');
        $this->loadTranslationsFrom(__DIR__ . '/resources/lang', 'sfl-inventory');
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
        $this->publishes([__DIR__ . '/public' => public_path('/')], 'sfl-inventory-assets');

        $this->mergeSidebar();
        $this->mergeInventoryPermissions();
        $this->registerMorphMap();
        $this->registerObservers();
    }

    public function register(): void
    {
        if (file_exists(__DIR__ . '/Config/config.php')) {
            $this->mergeConfigFrom(__DIR__ . '/Config/config.php', 'sfl-inventory');
        }

        $this->app->singleton(DocumentNumberService::class);
        $this->app->singleton(StockService::class);
        $this->app->singleton(InvOperatorScopeService::class);
    }

    private function mergeSidebar(): void
    {
        if (! file_exists($sidebar = __DIR__ . '/Config/sidebar.php')) {
            return;
        }

        // Sidebar entries are a numeric array — must array_merge, not mergeConfigFrom.
        Config::set('sidebar', array_merge(
            config('sidebar', []),
            require $sidebar
        ));
    }

    private function mergeInventoryPermissions(): void
    {
        if (! file_exists($file = __DIR__ . '/Config/permission.php')) {
            return;
        }

        $invPermissions = require $file;
        $main = config('permission', []);
        $main['modules'] = $main['modules'] ?? [];

        // The host's config/permission.php nests every group under a top-level
        // 'modules' key — both the Roles Setup screen and hasChildPermission()/
        // hasPermission() read it from there.
        foreach ($invPermissions as $group => $modules) {
            $main['modules'][$group] = $modules;
        }

        Config::set('permission', $main);
    }

    private function registerMorphMap(): void
    {
        // Referencing ::class here never triggers autoloading, so it's safe to
        // list entries for models that ship in later build batches.
        Relation::morphMap([
            'inv_item'                   => Models\InvItem::class,
            'inv_purchase_order'         => Models\InvPurchaseOrder::class,
            'inv_grn'                    => Models\InvGrn::class,
            'inv_requisition'            => Models\InvRequisition::class,
            'inv_issue'                  => Models\InvIssue::class,
            'inv_stock_transfer'         => Models\InvStockTransfer::class,
            'inv_production_consumption' => Models\InvProductionConsumption::class,
            'inv_finished_goods_receive' => Models\InvFinishedGoodsReceive::class,
            'inv_gate_pass'              => Models\InvGatePass::class,
            'inv_shipment'               => Models\InvShipment::class,
            'inv_stock_adjustment'       => Models\InvStockAdjustment::class,
        ]);
    }

    private function registerObservers(): void
    {
        Models\InvItem::observe(Observers\InvItemObserver::class);
        Models\InvPurchaseOrder::observe(Observers\InvPurchaseOrderObserver::class);
        Models\InvGrn::observe(Observers\InvGrnObserver::class);
        Models\InvRequisition::observe(Observers\InvRequisitionObserver::class);
        Models\InvIssue::observe(Observers\InvIssueObserver::class);
        Models\InvStockTransfer::observe(Observers\InvStockTransferObserver::class);
        Models\InvProductionConsumption::observe(Observers\InvProductionConsumptionObserver::class);
        Models\InvFinishedGoodsReceive::observe(Observers\InvFinishedGoodsReceiveObserver::class);
        Models\InvGatePass::observe(Observers\InvGatePassObserver::class);
        Models\InvShipment::observe(Observers\InvShipmentObserver::class);
        Models\InvStockAdjustment::observe(Observers\InvStockAdjustmentObserver::class);
    }
}
