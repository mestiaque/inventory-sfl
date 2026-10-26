<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_finished_goods_receives', function (Blueprint $table) {
            $table->id();
            $table->string('receive_no');
            $table->date('receive_date');
            $table->string('style')->nullable();
            $table->unsignedBigInteger('buyer_id')->nullable();
            $table->string('order_ref')->nullable();
            $table->unsignedBigInteger('store_id');
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('msfl_style_id')->nullable();
            $table->unsignedBigInteger('msfl_order_po_id')->nullable();
            $table->unsignedBigInteger('msfl_buyer_id')->nullable();
            $table->index('msfl_order_po_id');
            $table->index('msfl_style_id');
            $table->unique('receive_no');

            $table->foreign('buyer_id')->references('id')->on('inv_buyers')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('store_id')->references('id')->on('inv_stores')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_finished_goods_receives');
    }
};
