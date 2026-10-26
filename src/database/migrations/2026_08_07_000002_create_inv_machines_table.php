<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_machines', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->string('machine_no')->nullable();
            $table->string('model')->nullable();
            $table->string('origin')->nullable();
            $table->string('type')->nullable();
            $table->string('color')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->string('section')->nullable();
            $table->string('line')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique('code');

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('department_id')->references('id')->on('inv_departments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_machines');
    }
};
