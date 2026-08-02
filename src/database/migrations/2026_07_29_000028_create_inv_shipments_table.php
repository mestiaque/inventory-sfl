<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_shipments', function (Blueprint $table) {
            $table->id();
            $table->string('shipment_no')->unique();
            $table->date('shipment_date');
            $table->foreignId('buyer_id')->nullable()->constrained('inv_buyers')->restrictOnDelete();
            $table->string('invoice_no')->nullable();
            $table->string('packing_list_no')->nullable();
            $table->foreignId('gate_pass_id')->nullable()->constrained('inv_gate_passes')->nullOnDelete();
            // Only used (and required by the FormRequest) when gate_pass_id is
            // null — a direct shipment posts qty_out itself; a shipment linked
            // to a gate pass is a pure status document, stock already left via
            // the gate pass (see InvShipmentController@store).
            $table->foreignId('store_id')->nullable()->constrained('inv_stores')->restrictOnDelete();
            $table->enum('status', ['pending', 'dispatched', 'delivered'])->default('pending');
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_shipments');
    }
};
