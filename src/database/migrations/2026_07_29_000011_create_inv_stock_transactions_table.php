<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The stock ledger — the single source of truth for inventory. Insert-only
     * (see ME\SflInventory\Models\InvStockTransaction, which overrides
     * update()/delete() to throw). Current/reserved/available stock and stock
     * value are always computed from this table by
     * ME\SflInventory\Services\StockService — never stored on inv_items.
     */
    public function up(): void
    {
        Schema::create('inv_stock_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('inv_items')->restrictOnDelete();
            $table->foreignId('store_id')->constrained('inv_stores')->restrictOnDelete();
            $table->date('transaction_date');
            // opening | grn | issue | transfer | production_consumption | finished_goods | gate_pass | shipment | adjustment
            $table->string('transaction_type');
            $table->decimal('qty_in', 15, 4)->default(0);
            $table->decimal('qty_out', 15, 4)->default(0);
            $table->decimal('rate', 15, 2)->default(0);
            $table->decimal('value', 15, 2)->default(0);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('department_id')->nullable()->constrained('inv_departments')->nullOnDelete();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['item_id', 'store_id', 'transaction_date'], 'inv_stock_txn_item_store_date_idx');
            $table->index(['reference_type', 'reference_id'], 'inv_stock_txn_reference_idx');
            $table->index('transaction_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_stock_transactions');
    }
};
