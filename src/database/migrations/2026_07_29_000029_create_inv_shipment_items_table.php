<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_shipment_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shipment_id');
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('msfl_order_po_id')->nullable();
            $table->decimal('quantity', 15, 4);
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->index('msfl_order_po_id');

            $table->foreign('item_id')->references('id')->on('inv_items')->restrictOnDelete();
            $table->foreign('shipment_id')->references('id')->on('inv_shipments')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_shipment_items');
    }
};
