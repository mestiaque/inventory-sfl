<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_purchase_requisition_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_requisition_id')->constrained('inv_purchase_requisitions')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('inv_items')->restrictOnDelete();
            $table->decimal('requested_qty', 15, 4);
            $table->decimal('approved_qty', 15, 4)->default(0);
            $table->decimal('converted_qty', 15, 4)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_purchase_requisition_items');
    }
};
