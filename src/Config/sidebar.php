<?php

// Numeric array of sidebar "groups", array_merge'd into config('sidebar') at
// runtime by SflInventoryServiceProvider::boot(). Rendered by the host's
// existing admin/layouts/sidebar.blade.php. 'route' is a RAW path (not a named
// route). 'permission' is only enforced on leaf items — parent items with
// 'children' are shown iff any child is visible.

$base = '/admin/inventory';

return [
    [
        'group_title' => '',
        [
            'title'      => 'Inventory Management',
            'icon'       => 'fa-solid fa-boxes-stacked',
            'icon_color' => 'text-primary',
            'permission' => '',
            'order'      => 11,
            'children'   => [
                [
                    'title'      => 'Dashboard',
                    'icon'       => 'fa-solid fa-gauge',
                    'icon_color' => 'text-primary',
                    'permission' => 'inv_dashboard',
                    'route'      => $base,
                ],
                [
                    'title'      => 'User Guideline',
                    'icon'       => 'fa-solid fa-book-open',
                    'icon_color' => 'text-primary',
                    'permission' => 'inv_guideline',
                    'route'      => "$base/guideline",
                ],
                [
                    'title'      => 'My Signature',
                    'icon'       => 'fa-solid fa-signature',
                    'icon_color' => 'text-primary',
                    'permission' => 'inv_signature',
                    'route'      => "$base/my-signature",
                ],
                [
                    'title'      => 'Masters',
                    'icon'       => 'fa-solid fa-gear',
                    'icon_color' => 'text-secondary',
                    'permission' => '',
                    'children'   => [
                        ['title' => 'Stores', 'icon' => 'fa-solid fa-warehouse', 'icon_color' => 'text-info', 'permission' => 'inv_store', 'route' => "$base/stores"],
                        ['title' => 'Item Categories', 'icon' => 'fa-solid fa-sitemap', 'icon_color' => 'text-info', 'permission' => 'inv_item_category', 'route' => "$base/item-categories"],
                        ['title' => 'Units', 'icon' => 'fa-solid fa-ruler', 'icon_color' => 'text-info', 'permission' => 'inv_unit', 'route' => "$base/units"],
                        ['title' => 'Brands', 'icon' => 'fa-solid fa-tags', 'icon_color' => 'text-info', 'permission' => 'inv_brand', 'route' => "$base/brands"],
                        ['title' => 'Colors', 'icon' => 'fa-solid fa-palette', 'icon_color' => 'text-info', 'permission' => 'inv_color', 'route' => "$base/colors"],
                        ['title' => 'Sizes', 'icon' => 'fa-solid fa-ruler-combined', 'icon_color' => 'text-info', 'permission' => 'inv_size', 'route' => "$base/sizes"],
                        ['title' => 'Items', 'icon' => 'fa-solid fa-shirt', 'icon_color' => 'text-info', 'permission' => 'inv_item', 'route' => "$base/items"],
                        ['title' => 'Suppliers', 'icon' => 'fa-solid fa-truck-field', 'icon_color' => 'text-info', 'permission' => 'inv_supplier', 'route' => "$base/suppliers"],
                        ['title' => 'Buyers', 'icon' => 'fa-solid fa-handshake', 'icon_color' => 'text-info', 'permission' => 'inv_buyer', 'route' => "$base/buyers"],
                        ['title' => 'Departments', 'icon' => 'fa-solid fa-building', 'icon_color' => 'text-info', 'permission' => 'inv_department', 'route' => "$base/departments"],
                        ['title' => 'Operators / Store Incharge', 'icon' => 'fa-solid fa-user-gear', 'icon_color' => 'text-info', 'permission' => 'inv_operator', 'route' => "$base/operators"],
                        ['title' => 'Machines', 'icon' => 'fa-solid fa-industry', 'icon_color' => 'text-info', 'permission' => 'inv_machine', 'route' => "$base/machines"],
                    ],
                ],
                [
                    'title'      => 'Purchase',
                    'icon'       => 'fa-solid fa-cart-shopping',
                    'icon_color' => 'text-warning',
                    'permission' => '',
                    'children'   => [
                        ['title' => 'Store Order', 'icon' => 'fa-solid fa-file-signature', 'icon_color' => 'text-warning', 'permission' => 'inv_purchase_order', 'route' => "$base/purchase-orders"],
                        ['title' => 'Goods Receive (GRN)', 'icon' => 'fa-solid fa-truck-ramp-box', 'icon_color' => 'text-warning', 'permission' => 'inv_grn', 'route' => "$base/grns"],
                    ],
                ],
                [
                    'title'      => 'Main Store Inventory',
                    'icon'       => 'fa-solid fa-boxes-packing',
                    'icon_color' => 'text-success',
                    'permission' => '',
                    'children'   => [
                        ['title' => 'Table View', 'icon' => 'fa-solid fa-table', 'icon_color' => 'text-success', 'permission' => 'inv_stock_overview', 'route' => "$base/stock-overview"],
                        ['title' => 'Store Overview (Cards)', 'icon' => 'fa-solid fa-border-all', 'icon_color' => 'text-success', 'permission' => 'inv_stock_overview', 'route' => "$base/store-overview"],
                    ],
                ],
                [
                    'title'      => 'Requisition & Issue',
                    'icon'       => 'fa-solid fa-clipboard-list',
                    'icon_color' => 'text-primary',
                    'permission' => '',
                    'children'   => [
                        ['title' => 'Store Requisitions', 'icon' => 'fa-solid fa-file-pen', 'icon_color' => 'text-primary', 'permission' => 'inv_requisition', 'route' => "$base/requisitions"],
                        ['title' => 'Store Issues', 'icon' => 'fa-solid fa-dolly', 'icon_color' => 'text-primary', 'permission' => 'inv_issue', 'route' => "$base/issues"],
                    ],
                ],
                [
                    'title'      => 'Stock Transfer',
                    'icon'       => 'fa-solid fa-right-left',
                    'icon_color' => 'text-info',
                    'permission' => 'inv_transfer',
                    'route'      => "$base/transfers",
                ],
                [
                    'title'      => 'Production Consumption',
                    'icon'       => 'fa-solid fa-industry',
                    'icon_color' => 'text-danger',
                    'permission' => 'inv_production',
                    'route'      => "$base/production-consumptions",
                ],
                [
                    'title'      => 'Finished Goods',
                    'icon'       => 'fa-solid fa-box-open',
                    'icon_color' => 'text-success',
                    'permission' => '',
                    'children'   => [
                        ['title' => 'FG Receive', 'icon' => 'fa-solid fa-box', 'icon_color' => 'text-success', 'permission' => 'inv_fg_receive', 'route' => "$base/fg-receives"],
                        ['title' => 'Shipment', 'icon' => 'fa-solid fa-ship', 'icon_color' => 'text-success', 'permission' => 'inv_shipment', 'route' => "$base/shipments"],
                        ['title' => 'Gate Pass', 'icon' => 'fa-solid fa-id-card', 'icon_color' => 'text-success', 'permission' => 'inv_gate_pass', 'route' => "$base/gate-passes"],
                    ],
                ],
                [
                    'title'      => 'Stock Adjustment',
                    'icon'       => 'fa-solid fa-scale-unbalanced',
                    'icon_color' => 'text-danger',
                    'permission' => 'inv_adjustment',
                    'route'      => "$base/adjustments",
                ],
                [
                    'title'      => 'Stock Ledger',
                    'icon'       => 'fa-solid fa-book',
                    'icon_color' => 'text-secondary',
                    'permission' => 'inv_stock_ledger',
                    'route'      => "$base/stock-ledger",
                ],
                [
                    'title'      => 'Broken Needle',
                    'icon'       => 'fa-solid fa-syringe',
                    'icon_color' => 'text-danger',
                    'permission' => '',
                    'children'   => [
                        ['title' => 'Entries', 'icon' => 'fa-solid fa-arrow-right', 'icon_color' => 'text-danger', 'permission' => 'inv_broken_needle', 'route' => "$base/broken-needles"],
                        ['title' => 'Monthly Report', 'icon' => 'fa-solid fa-arrow-right', 'icon_color' => 'text-danger', 'permission' => 'inv_broken_needle', 'route' => "$base/broken-needles/report"],
                    ],
                ],
                [
                    'title'      => 'Reports',
                    'icon'       => 'fa-solid fa-chart-line',
                    'icon_color' => 'text-info',
                    'permission' => '',
                    'children'   => [
                        ['title' => 'Store Inventory Report', 'icon' => 'fa-solid fa-arrow-right', 'icon_color' => 'text-info', 'permission' => 'inv_report', 'route' => "$base/reports/store-inventory-report"],
                        ['title' => 'Current Stock', 'icon' => 'fa-solid fa-arrow-right', 'icon_color' => 'text-info', 'permission' => 'inv_report', 'route' => "$base/reports/current-stock"],
                        ['title' => 'Stock Summary', 'icon' => 'fa-solid fa-arrow-right', 'icon_color' => 'text-info', 'permission' => 'inv_report', 'route' => "$base/reports/stock-summary"],
                        ['title' => 'Item History', 'icon' => 'fa-solid fa-arrow-right', 'icon_color' => 'text-info', 'permission' => 'inv_report', 'route' => "$base/reports/item-history"],
                        ['title' => 'Store Wise Stock', 'icon' => 'fa-solid fa-arrow-right', 'icon_color' => 'text-info', 'permission' => 'inv_report', 'route' => "$base/reports/store-wise-stock"],
                        ['title' => 'Department Wise Consumption', 'icon' => 'fa-solid fa-arrow-right', 'icon_color' => 'text-info', 'permission' => 'inv_report', 'route' => "$base/reports/department-consumption"],
                        ['title' => 'Supplier Wise Purchase', 'icon' => 'fa-solid fa-arrow-right', 'icon_color' => 'text-info', 'permission' => 'inv_report', 'route' => "$base/reports/supplier-purchase"],
                        ['title' => 'GRN Report', 'icon' => 'fa-solid fa-arrow-right', 'icon_color' => 'text-info', 'permission' => 'inv_report', 'route' => "$base/reports/grn"],
                        ['title' => 'Item Wise Goods Receive Report', 'icon' => 'fa-solid fa-arrow-right', 'icon_color' => 'text-info', 'permission' => 'inv_report', 'route' => "$base/reports/grn-item-wise"],
                        ['title' => 'Expiry Tracking', 'icon' => 'fa-solid fa-arrow-right', 'icon_color' => 'text-info', 'permission' => 'inv_report', 'route' => "$base/reports/expiry-tracking"],
                        ['title' => 'Issue Report', 'icon' => 'fa-solid fa-arrow-right', 'icon_color' => 'text-info', 'permission' => 'inv_report', 'route' => "$base/reports/issue"],
                        ['title' => 'Gate Pass Report', 'icon' => 'fa-solid fa-arrow-right', 'icon_color' => 'text-info', 'permission' => 'inv_report', 'route' => "$base/reports/gate-pass"],
                        ['title' => 'Shipment Report', 'icon' => 'fa-solid fa-arrow-right', 'icon_color' => 'text-info', 'permission' => 'inv_report', 'route' => "$base/reports/shipment"],
                        ['title' => 'Low Stock Report', 'icon' => 'fa-solid fa-arrow-right', 'icon_color' => 'text-info', 'permission' => 'inv_report', 'route' => "$base/reports/low-stock"],
                        ['title' => 'Dead Stock Report', 'icon' => 'fa-solid fa-arrow-right', 'icon_color' => 'text-info', 'permission' => 'inv_report', 'route' => "$base/reports/dead-stock"],
                        ['title' => 'Stock Valuation', 'icon' => 'fa-solid fa-arrow-right', 'icon_color' => 'text-info', 'permission' => 'inv_report', 'route' => "$base/reports/stock-valuation"],
                    ],
                ],
            ],
        ],
    ],
];
