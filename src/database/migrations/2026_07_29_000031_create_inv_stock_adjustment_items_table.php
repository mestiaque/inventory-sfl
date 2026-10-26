<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_stock_adjustment_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('adjustment_id');
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('color_id')->nullable();
            $table->unsignedBigInteger('size_id')->nullable();
            $table->decimal('system_qty', 15, 4)->default(0.0000);
            $table->decimal('physical_qty', 15, 4)->default(0.0000);
            $table->decimal('difference_qty', 15, 4)->default(0.0000);
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->foreign('adjustment_id')->references('id')->on('inv_stock_adjustments')->cascadeOnDelete();
            $table->foreign('color_id')->references('id')->on('inv_colors')->nullOnDelete();
            $table->foreign('item_id')->references('id')->on('inv_items')->restrictOnDelete();
            $table->foreign('size_id')->references('id')->on('inv_sizes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_stock_adjustment_items');
    }
};
