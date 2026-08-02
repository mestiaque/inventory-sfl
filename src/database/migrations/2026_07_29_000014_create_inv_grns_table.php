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
            $table->string('grn_number')->unique();
            $table->foreignId('purchase_order_id')->nullable()->constrained('inv_purchase_orders')->nullOnDelete();
            $table->foreignId('store_id')->constrained('inv_stores')->restrictOnDelete();
            $table->foreignId('supplier_id')->constrained('inv_suppliers')->restrictOnDelete();
            $table->date('receive_date');
            $table->enum('status', ['posted', 'cancelled'])->default('posted');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_grns');
    }
};
