<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_stock_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('color_id')->nullable();
            $table->unsignedBigInteger('size_id')->nullable();
            $table->unsignedBigInteger('store_id');
            $table->date('transaction_date');
            $table->string('transaction_type');
            $table->decimal('qty_in', 15, 4)->default(0.0000);
            $table->decimal('qty_out', 15, 4)->default(0.0000);
            $table->decimal('rate', 15, 2)->default(0.00);
            $table->decimal('value', 15, 2)->default(0.00);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->index('transaction_type');
            $table->index(['item_id', 'store_id', 'transaction_date'], 'inv_stock_txn_item_store_date_idx');
            $table->index(['reference_type', 'reference_id'], 'inv_stock_txn_reference_idx');
            $table->index(['item_id', 'store_id', 'color_id', 'size_id', 'transaction_date'], 'inv_stock_txn_variant_idx');

            $table->foreign('color_id')->references('id')->on('inv_colors')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('department_id')->references('id')->on('inv_departments')->nullOnDelete();
            $table->foreign('item_id')->references('id')->on('inv_items')->restrictOnDelete();
            $table->foreign('size_id')->references('id')->on('inv_sizes')->nullOnDelete();
            $table->foreign('store_id')->references('id')->on('inv_stores')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_stock_transactions');
    }
};
