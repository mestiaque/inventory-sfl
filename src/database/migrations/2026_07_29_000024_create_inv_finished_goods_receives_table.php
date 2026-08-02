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
            $table->string('receive_no')->unique();
            $table->date('receive_date');
            $table->string('style')->nullable();
            $table->foreignId('buyer_id')->nullable()->constrained('inv_buyers')->restrictOnDelete();
            $table->string('order_ref')->nullable();
            $table->foreignId('store_id')->constrained('inv_stores')->restrictOnDelete();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_finished_goods_receives');
    }
};
