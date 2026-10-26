<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_requisitions', function (Blueprint $table) {
            $table->id();
            $table->string('requisition_no');
            $table->date('requisition_date');
            $table->unsignedBigInteger('department_id');
            $table->enum('requisition_for', ['fabrics', 'accessories', 'machine_parts', 'equipment', 'stationery'])->nullable();
            $table->unsignedBigInteger('buyer_id')->nullable();
            $table->string('style')->nullable();
            $table->string('order_ref')->nullable();
            $table->unsignedBigInteger('store_id');
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->unsignedBigInteger('received_by')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'issued', 'partially_issued'])->default('pending');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_remarks')->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('msfl_style_id')->nullable();
            $table->unsignedBigInteger('msfl_order_po_id')->nullable();
            $table->unsignedBigInteger('msfl_buyer_id')->nullable();
            $table->index('msfl_order_po_id');
            $table->index('msfl_style_id');
            $table->index('received_by', 'inv_requisitions_received_by_foreign');
            $table->unique('requisition_no');

            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('buyer_id')->references('id')->on('inv_buyers')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('department_id')->references('id')->on('inv_departments')->restrictOnDelete();
            $table->foreign('requested_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('store_id')->references('id')->on('inv_stores')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_requisitions');
    }
};
