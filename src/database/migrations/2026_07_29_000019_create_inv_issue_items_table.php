<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_issue_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('issue_id');
            $table->unsignedBigInteger('requisition_item_id')->nullable();
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('color_id')->nullable();
            $table->unsignedBigInteger('size_id')->nullable();
            $table->decimal('issued_qty', 15, 4);
            $table->decimal('department_received_qty', 15, 4)->default(0.0000);
            $table->decimal('unit_rate', 15, 2)->default(0.00);
            $table->decimal('amount', 15, 2)->default(0.00);
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->foreign('color_id')->references('id')->on('inv_colors')->nullOnDelete();
            $table->foreign('issue_id')->references('id')->on('inv_issues')->cascadeOnDelete();
            $table->foreign('item_id')->references('id')->on('inv_items')->restrictOnDelete();
            $table->foreign('requisition_item_id')->references('id')->on('inv_requisition_items')->nullOnDelete();
            $table->foreign('size_id')->references('id')->on('inv_sizes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_issue_items');
    }
};
