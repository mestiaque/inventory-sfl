<?php

// Who gets emailed when something needs approval — one list per approval
// module. Read by ME\SflInventory\Approvals\Concerns\ResolvesConfiguredRecipients,
// used by every ME\SflInventory\Approvals\*ApprovalHandler.
//
// A module left empty here falls back to the default: every user holding
// that module's '.approve' permission is notified. Fill a module's list in
// and it takes over COMPLETELY for that module — only the addresses listed
// are notified, whether or not they hold the approve permission, and
// whether or not other users also hold it.
//
// To override without touching the package, create config/sfl-inventory-mail.php
// in the host app with the same shape — merged the same way config.php is
// (see SflInventoryServiceProvider::register()); any key the host sets wins.
return [
    'approval_recipients' => [
        // Purchase Requisition -> Admin Approval
        'inventory.purchase_requisition' => [
            // 'purchase.manager@example.com',
        ],

        // GRN -> Receive Approval
        'inventory.grn_receive' => [
            // 'store.manager@example.com',
        ],

        // Store Requisition -> Approval
        'inventory.requisition' => [
            // 'floor.manager@example.com',
        ],
    ],
];
