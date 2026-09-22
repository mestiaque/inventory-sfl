<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * A Store Order is now auto-created the instant its Purchase Requisition
     * is approved — no supplier is known yet at that point. Supplier (and
     * rate) is picked later, at GRN Receive time. ->change() would need
     * doctrine/dbal, which isn't installed here; MySQL lets MODIFY relax a
     * column's nullability directly without touching its existing foreign key.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE inv_purchase_orders MODIFY supplier_id BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE inv_purchase_orders MODIFY supplier_id BIGINT UNSIGNED NOT NULL');
    }
};
