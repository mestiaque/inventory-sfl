<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_grns', function (Blueprint $table) {
            $table->id();
            $table->string('grn_number');
            $table->unsignedBigInteger('purchase_order_id')->nullable();
            $table->enum('source_type', ['purchase', 'buyer_supplied'])->default('purchase');
            $table->unsignedBigInteger('store_id');
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->unsignedBigInteger('buyer_id')->nullable();
            $table->string('style')->nullable();
            $table->string('order_ref')->nullable();
            $table->string('challan_invoice_no')->nullable();
            $table->date('receive_date');
            $table->enum('status', ['pending', 'posted', 'rejected', 'cancelled'])->default('posted');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_remarks')->nullable();
            $table->decimal('total_amount', 15, 2)->default(0.00);
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('received_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('msfl_style_id')->nullable();
            $table->unsignedBigInteger('msfl_order_po_id')->nullable();
            $table->unsignedBigInteger('msfl_buyer_id')->nullable();
            $table->unique('grn_number');
            $table->index('msfl_order_po_id');
            $table->index('msfl_style_id');
            $table->index('received_by', 'inv_grns_received_by_foreign');

            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('buyer_id')->references('id')->on('inv_buyers')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('purchase_order_id')->references('id')->on('inv_purchase_orders')->nullOnDelete();
            $table->foreign('store_id')->references('id')->on('inv_stores')->restrictOnDelete();
            $table->foreign('supplier_id')->references('id')->on('inv_suppliers')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_grns');
    }
};
