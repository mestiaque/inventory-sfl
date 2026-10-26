<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_stock_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transfer_id');
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('color_id')->nullable();
            $table->unsignedBigInteger('size_id')->nullable();
            $table->decimal('quantity', 15, 4);
            $table->decimal('received_qty', 15, 4)->default(0.0000);
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->foreign('color_id')->references('id')->on('inv_colors')->nullOnDelete();
            $table->foreign('item_id')->references('id')->on('inv_items')->restrictOnDelete();
            $table->foreign('size_id')->references('id')->on('inv_sizes')->nullOnDelete();
            $table->foreign('transfer_id')->references('id')->on('inv_stock_transfers')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_stock_transfer_items');
    }
};
