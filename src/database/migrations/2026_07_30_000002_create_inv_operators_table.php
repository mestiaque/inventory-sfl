<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_operators', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->enum('designation', ['operator', 'store_incharge', 'store_manager'])->default('operator');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->unsignedBigInteger('store_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique('code');
            $table->unique('user_id');

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('store_id')->references('id')->on('inv_stores')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_operators');
    }
};
