<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number');
            $table->unsignedBigInteger('purchase_requisition_id')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->date('order_date');
            $table->date('expected_date')->nullable();
            $table->enum('status', ['draft', 'approved', 'received', 'closed', 'cancelled'])->default('draft');
            $table->decimal('total_amount', 15, 2)->default(0.00);
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique('po_number');

            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('purchase_requisition_id')->references('id')->on('inv_purchase_requisitions')->nullOnDelete();
            $table->foreign('supplier_id')->references('id')->on('inv_suppliers')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_purchase_orders');
    }
};
