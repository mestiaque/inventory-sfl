<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('adjustment_no');
            $table->unsignedBigInteger('store_id');
            $table->date('adjustment_date');
            $table->enum('type', ['damage', 'lost', 'excess', 'physical_count'])->default('physical_count');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique('adjustment_no');

            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('store_id')->references('id')->on('inv_stores')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_stock_adjustments');
    }
};
