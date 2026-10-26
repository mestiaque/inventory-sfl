<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_issues', function (Blueprint $table) {
            $table->id();
            $table->string('issue_no');
            $table->unsignedBigInteger('requisition_id')->nullable();
            $table->unsignedBigInteger('store_id');
            $table->unsignedBigInteger('to_store_id')->nullable();
            $table->unsignedBigInteger('department_id');
            $table->enum('status', ['pending', 'authorized', 'approved'])->default('pending');
            $table->unsignedBigInteger('buyer_id')->nullable();
            $table->string('style')->nullable();
            $table->string('order_ref')->nullable();
            $table->date('issue_date');
            $table->unsignedBigInteger('issued_by')->nullable();
            $table->unsignedBigInteger('authorized_by')->nullable();
            $table->timestamp('authorized_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('department_received_by')->nullable();
            $table->timestamp('department_received_at')->nullable();
            $table->enum('department_receive_status', ['pending', 'partial', 'full'])->default('pending');
            $table->text('department_receive_remarks')->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('msfl_style_id')->nullable();
            $table->unsignedBigInteger('msfl_order_po_id')->nullable();
            $table->unsignedBigInteger('msfl_buyer_id')->nullable();
            $table->unique('issue_no');
            $table->index('msfl_order_po_id');
            $table->index('msfl_style_id');

            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('authorized_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('buyer_id')->references('id')->on('inv_buyers')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('department_id')->references('id')->on('inv_departments')->restrictOnDelete();
            $table->foreign('department_received_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('issued_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('requisition_id')->references('id')->on('inv_requisitions')->nullOnDelete();
            $table->foreign('store_id')->references('id')->on('inv_stores')->restrictOnDelete();
            $table->foreign('to_store_id')->references('id')->on('inv_stores')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_issues');
    }
};
