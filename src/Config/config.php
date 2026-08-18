<?php

return [
    'name' => 'Inventory Management',

    'route' => [
        'prefix'     => 'admin/inventory',
        'as'         => 'inventory.',
        'middleware' => ['web', 'auth'],
    ],

    'company' => [
        'name'    => env('COMPANY_NAME', 'SUHANA FASHIONS LTD.'),
        'address' => env('COMPANY_ADDRESS', 'Kathgora, Ashulia, Savar, Dhaka, Bangladesh'),
    ],

    // Prefixes used by ME\SflInventory\Services\DocumentNumberService to
    // auto-generate unique document numbers (e.g. PO-000001, GRN-000001).
    // 'company' prefixes item codes: SFL-{category code}-{seq}, e.g. SFL-SW-055.
    'document_prefixes' => [
        'company'        => env('SFL_INVENTORY_COMPANY_CODE', 'SFL'),
        'item'           => 'ITM',
        'purchase_order' => 'SO',
        'grn'            => 'GRN',
        'requisition'    => 'REQ',
        'issue'          => 'ISS',
        'transfer'       => 'TRF',
        'consumption'    => 'CONS',
        'fg_receive'     => 'FGR',
        'gate_pass'      => 'GP',
        'shipment'       => 'SHP',
        'adjustment'     => 'ADJ',
    ],

    // An item with no outbound movement in this many days is flagged as dead stock.
    'dead_stock_days' => 90,
];
