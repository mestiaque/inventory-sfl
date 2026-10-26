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
            $table->unsignedBigInteger('grn_id');
            $table->unsignedBigInteger('purchase_order_item_id')->nullable();
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('color_id')->nullable();
            $table->unsignedBigInteger('size_id')->nullable();
            $table->decimal('ordered_qty', 15, 4)->default(0.0000);
            $table->decimal('received_qty', 15, 4);
            $table->decimal('rejected_qty', 15, 4)->default(0.0000);
            $table->decimal('rate', 15, 2);
            $table->decimal('amount', 15, 2);
            $table->string('lot_no')->nullable();
            $table->string('batch_no')->nullable();
            $table->date('expiry_date')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->foreign('color_id')->references('id')->on('inv_colors')->nullOnDelete();
            $table->foreign('grn_id')->references('id')->on('inv_grns')->cascadeOnDelete();
            $table->foreign('item_id')->references('id')->on('inv_items')->restrictOnDelete();
            $table->foreign('purchase_order_item_id')->references('id')->on('inv_purchase_order_items')->nullOnDelete();
            $table->foreign('size_id')->references('id')->on('inv_sizes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_grn_items');
    }
};
