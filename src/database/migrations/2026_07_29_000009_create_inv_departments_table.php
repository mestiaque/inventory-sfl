<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->unsignedBigInteger('default_store_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique('code');

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('default_store_id')->references('id')->on('inv_stores')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_departments');
    }
};
