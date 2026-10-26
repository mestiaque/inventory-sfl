<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_gate_passes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shipment_id')->nullable();
            $table->string('gate_pass_no');
            $table->date('gate_pass_date');
            $table->unsignedBigInteger('buyer_id')->nullable();
            $table->string('vehicle_no')->nullable();
            $table->string('driver_name')->nullable();
            $table->string('driver_contact')->nullable();
            $table->unsignedBigInteger('store_id');
            $table->enum('status', ['pending', 'issued', 'cancelled'])->default('pending');
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique('gate_pass_no');

            $table->foreign('buyer_id')->references('id')->on('inv_buyers')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('store_id')->references('id')->on('inv_stores')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_gate_passes');
    }
};
