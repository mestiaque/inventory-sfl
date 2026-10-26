<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_sample_challans', function (Blueprint $table) {
            $table->id();
            $table->string('challan_no', 50);
            $table->date('challan_date');
            $table->unsignedBigInteger('store_id')->nullable();
            $table->unsignedBigInteger('msfl_buyer_id')->nullable();
            $table->unsignedBigInteger('msfl_sample_type_id')->nullable();
            $table->string('sample_type_name', 50)->nullable();
            $table->string('to_name')->nullable();
            $table->text('to_address')->nullable();
            $table->string('to_contact', 100)->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('issued_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique('challan_no');
            $table->index('msfl_buyer_id');
            $table->index('msfl_sample_type_id');

            $table->foreign('issued_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('store_id')->references('id')->on('inv_stores')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_sample_challans');
    }
};
