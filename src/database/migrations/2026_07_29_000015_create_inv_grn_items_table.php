<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_grn_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grn_id')->constrained('inv_grns')->cascadeOnDelete();
            $table->foreignId('purchase_order_item_id')->nullable()->constrained('inv_purchase_order_items')->nullOnDelete();
            $table->foreignId('item_id')->constrained('inv_items')->restrictOnDelete();
            $table->decimal('ordered_qty', 15, 4)->default(0);
            $table->decimal('received_qty', 15, 4);
            $table->decimal('rejected_qty', 15, 4)->default(0);
            $table->decimal('rate', 15, 2);
            $table->decimal('amount', 15, 2);
            $table->string('lot_no')->nullable();
            $table->string('batch_no')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_grn_items');
    }
};
