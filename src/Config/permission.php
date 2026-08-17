<?php

// Merged into config('permission')['modules']['INVENTORY MANAGEMENT'] at runtime
// by SflInventoryServiceProvider::mergeInventoryPermissions(). Feeds the host's
// existing Roles Setup checkbox UI — permission strings used elsewhere are always
// "<module_key>.<action_key>", e.g. 'inv_item.list', 'inv_grn.add'.

$crud = ['list' => 'List', 'add' => 'Create', 'edit' => 'Edit', 'view' => 'View', 'delete' => 'Delete', 'all' => 'All'];

return [
    'INVENTORY MANAGEMENT' => [
        'inv_dashboard' => [
            'label' => 'Inventory Dashboard',
            'permissions' => ['view' => 'View', 'all' => 'All'],
        ],
        'inv_guideline' => [
            'label' => 'User Guideline',
            'permissions' => ['view' => 'View', 'all' => 'All'],
        ],
        'inv_signature' => [
            'label' => 'My Signature',
            'permissions' => ['edit' => 'Edit', 'all' => 'All'],
        ],
        'inv_store' => [
            'label' => 'Stores',
            'permissions' => $crud,
        ],
        'inv_item_category' => [
            'label' => 'Item Categories',
            'permissions' => $crud,
        ],
        'inv_unit' => [
            'label' => 'Units',
            'permissions' => $crud,
        ],
        'inv_brand' => [
            'label' => 'Brands',
            'permissions' => $crud,
        ],
        'inv_color' => [
            'label' => 'Colors',
            'permissions' => $crud,
        ],
        'inv_size' => [
            'label' => 'Sizes',
            'permissions' => $crud,
        ],
        'inv_operator' => [
            'label' => 'Operators / Store Incharge',
            'permissions' => $crud,
        ],
        'inv_item' => [
            'label' => 'Item Master',
            'permissions' => $crud + ['import' => 'Import', 'export' => 'Export', 'force_delete' => 'Force Delete'],
        ],
        'inv_supplier' => [
            'label' => 'Suppliers',
            'permissions' => $crud,
        ],
        'inv_buyer' => [
            'label' => 'Buyers',
            'permissions' => $crud,
        ],
        'inv_department' => [
            'label' => 'Departments',
            'permissions' => $crud + ['force_delete' => 'Force Delete'],
        ],
        'inv_machine' => [
            'label' => 'Machines',
            'permissions' => $crud,
        ],
        'inv_purchase_order' => [
            'label' => 'Purchase Orders',
            'permissions' => $crud + ['approve' => 'Approve', 'print' => 'Print', 'export' => 'Export'],
        ],
        'inv_grn' => [
            'label' => 'Goods Receive Note (GRN)',
            'permissions' => $crud + ['print' => 'Print', 'export' => 'Export'],
        ],
        'inv_stock_overview' => [
            'label' => 'Main Store Inventory',
            'permissions' => ['view' => 'View', 'export' => 'Export', 'all' => 'All'],
        ],
        'inv_requisition' => [
            'label' => 'Store Requisition',
            'permissions' => $crud + ['approve' => 'Approve', 'reject' => 'Reject', 'print' => 'Print', 'export' => 'Export', 'force_delete' => 'Force Delete'],
        ],
        'inv_issue' => [
            'label' => 'Store Issue & Department Receive',
            'permissions' => $crud + ['authorize' => 'Authorize', 'approve' => 'Approve', 'receive' => 'Department Receive', 'print' => 'Print', 'export' => 'Export'],
        ],
        'inv_transfer' => [
            'label' => 'Internal Stock Transfer',
            'permissions' => $crud + ['approve' => 'Approve', 'receive' => 'Receive', 'print' => 'Print', 'export' => 'Export'],
        ],
        'inv_production' => [
            'label' => 'Production Consumption',
            'permissions' => $crud + ['export' => 'Export'],
        ],
        'inv_fg_receive' => [
            'label' => 'Finished Goods Receive',
            'permissions' => $crud + ['print' => 'Print', 'export' => 'Export'],
        ],
        'inv_gate_pass' => [
            'label' => 'Gate Pass',
            'permissions' => $crud + ['approve' => 'Approve', 'print' => 'Print', 'export' => 'Export'],
        ],
        'inv_shipment' => [
            'label' => 'Shipment',
            'permissions' => $crud + ['print' => 'Print', 'export' => 'Export'],
        ],
        'inv_adjustment' => [
            'label' => 'Stock Adjustment',
            'permissions' => $crud + ['approve' => 'Approve', 'export' => 'Export'],
        ],
        'inv_stock_ledger' => [
            'label' => 'Stock Ledger',
            'permissions' => ['view' => 'View', 'export' => 'Export', 'all' => 'All'],
        ],
        'inv_report' => [
            'label' => 'Inventory Reports',
            'permissions' => ['view' => 'View', 'export' => 'Export', 'all' => 'All'],
        ],
        'inv_broken_needle' => [
            'label' => 'Broken Needle',
            'permissions' => $crud + ['export' => 'Export', 'force_delete' => 'Force Delete'],
        ],
    ],
];
