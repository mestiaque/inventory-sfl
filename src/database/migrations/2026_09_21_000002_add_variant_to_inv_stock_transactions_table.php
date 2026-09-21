<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Color/Size become a real stock-tracking dimension, not just a label on
     * a document — an item with no fixed color/size of its own (a "generic"
     * multi-variant item, e.g. plain "Button") can now be received/issued as
     * distinct item+color+size balances. Both columns are nullable: an item
     * that already has a fixed color_id/size_id on its own master record
     * (inv_items) doesn't need a per-transaction pick, and every existing
     * ledger row (posted before this column existed) is simply "no variant
     * recorded" — it keeps aggregating correctly into item-wide totals since
     * StockService only filters on color_id/size_id when a caller asks for
     * a specific variant.
     */
    public function up(): void
    {
        Schema::table('inv_stock_transactions', function (Blueprint $table) {
            $table->foreignId('color_id')->nullable()->after('item_id')->constrained('inv_colors')->nullOnDelete();
            $table->foreignId('size_id')->nullable()->after('color_id')->constrained('inv_sizes')->nullOnDelete();
            $table->index(['item_id', 'store_id', 'color_id', 'size_id', 'transaction_date'], 'inv_stock_txn_variant_idx');
        });
    }

    public function down(): void
    {
        Schema::table('inv_stock_transactions', function (Blueprint $table) {
            $table->dropIndex('inv_stock_txn_variant_idx');
            $table->dropConstrainedForeignId('color_id');
            $table->dropConstrainedForeignId('size_id');
        });
    }
};
