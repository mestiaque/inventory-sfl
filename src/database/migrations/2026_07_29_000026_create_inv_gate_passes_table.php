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
            $table->string('gate_pass_no')->unique();
            $table->date('gate_pass_date');
            $table->foreignId('buyer_id')->nullable()->constrained('inv_buyers')->restrictOnDelete();
            $table->string('vehicle_no')->nullable();
            $table->string('driver_name')->nullable();
            $table->string('driver_contact')->nullable();
            $table->foreignId('store_id')->constrained('inv_stores')->restrictOnDelete();
            $table->enum('status', ['pending', 'issued', 'cancelled'])->default('pending');
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_gate_passes');
    }
};
