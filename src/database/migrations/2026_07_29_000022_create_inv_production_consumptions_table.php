<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_production_consumptions', function (Blueprint $table) {
            $table->id();
            $table->string('consumption_no');
            $table->unsignedBigInteger('department_id');
            $table->unsignedBigInteger('issue_id')->nullable();
            $table->string('style')->nullable();
            $table->string('order_ref')->nullable();
            $table->date('consumption_date');
            $table->unsignedBigInteger('store_id');
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('msfl_style_id')->nullable();
            $table->unsignedBigInteger('msfl_order_po_id')->nullable();
            $table->unsignedBigInteger('msfl_buyer_id')->nullable();
            $table->unique('consumption_no');
            $table->index('msfl_order_po_id');
            $table->index('msfl_style_id');

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('department_id')->references('id')->on('inv_departments')->restrictOnDelete();
            $table->foreign('issue_id')->references('id')->on('inv_issues')->nullOnDelete();
            $table->foreign('store_id')->references('id')->on('inv_stores')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_production_consumptions');
    }
};
