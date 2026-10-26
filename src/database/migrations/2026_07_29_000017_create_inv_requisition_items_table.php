<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_requisition_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('requisition_id');
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('color_id')->nullable();
            $table->unsignedBigInteger('size_id')->nullable();
            $table->decimal('requested_qty', 15, 4);
            $table->decimal('approved_qty', 15, 4)->default(0.0000);
            $table->decimal('issued_qty', 15, 4)->default(0.0000);
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->foreign('color_id')->references('id')->on('inv_colors')->nullOnDelete();
            $table->foreign('item_id')->references('id')->on('inv_items')->restrictOnDelete();
            $table->foreign('requisition_id')->references('id')->on('inv_requisitions')->cascadeOnDelete();
            $table->foreign('size_id')->references('id')->on('inv_sizes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_requisition_items');
    }
};
