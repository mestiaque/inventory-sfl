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
            $table->string('shipment_no');
            $table->date('shipment_date');
            $table->unsignedBigInteger('buyer_id')->nullable();
            $table->string('invoice_no')->nullable();
            $table->string('packing_list_no')->nullable();
            $table->unsignedBigInteger('gate_pass_id')->nullable();
            $table->unsignedBigInteger('store_id')->nullable();
            $table->enum('status', ['pending', 'dispatched', 'delivered'])->default('pending');
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique('shipment_no');

            $table->foreign('buyer_id')->references('id')->on('inv_buyers')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('gate_pass_id')->references('id')->on('inv_gate_passes')->nullOnDelete();
            $table->foreign('store_id')->references('id')->on('inv_stores')->restrictOnDelete();
        });

        // inv_gate_passes and inv_shipments reference each other, so this key is added once both exist.
        Schema::table('inv_gate_passes', function (Blueprint $table) {
            $table->foreign('shipment_id')->references('id')->on('inv_shipments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('inv_gate_passes', function (Blueprint $table) {
            $table->dropForeign('inv_gate_passes_shipment_id_foreign');
        });
        Schema::dropIfExists('inv_shipments');
    }
};
