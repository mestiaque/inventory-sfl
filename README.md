# SFL Inventory

Garments Factory Inventory Management module for Laravel (Suhana Fashions Limited), by M. Estiaque Ahmed Khan.

A full ERP-grade inventory system covering item master, multi-store stock, purchase orders, GRN, store requisition/issue, internal transfers, production consumption, finished goods, gate pass, shipment, stock adjustment, and a transaction-based stock ledger (current stock is always derived from `inv_stock_transactions`, never stored).

## Installation

Installed via a composer path repository into the host app (see the host's `composer.json` `repositories` block):

```bash
composer require mestiaque/sfl-inventory
php artisan migrate
```

The service provider is auto-discovered and wires itself into the host's shared `sidebar` and `permission` config at boot — no host-app changes are needed except the one dashboard-widget include documented in the build plan.

## Conventions

- All database tables are prefixed `inv_`.
- Routes: `admin/inventory/*`, route name prefix `inventory.`.
- Views: `sfl-inventory::admin.*`.
- Permissions: `<module_key>.<action>` (e.g. `inv_item.list`), fed into the host's existing Roles Setup screen under the `INVENTORY MANAGEMENT` group.
- Layout/permissions/sidebar/dashboard patterns mirror the `acc-sfl` (Accounts) sibling package.
