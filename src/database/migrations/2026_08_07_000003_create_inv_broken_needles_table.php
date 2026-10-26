<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per broken-needle incident — who broke it, how many pieces, when.
 * employee_id has no real FK: hr_employees.id is `int unsigned` while every
 * other FK column in this package is `bigint unsigned`, which errno-150s a
 * real foreign key (same reason inv_requisitions.received_by and
 * inv_operators.employee_id skip it); app-level validation covers it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_broken_needles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('machine_id')->nullable();
            $table->string('line_no')->nullable();
            $table->string('needle_type')->nullable();
            $table->string('needle_size')->nullable();
            $table->unsignedBigInteger('buyer_id')->nullable();
            $table->string('style')->nullable();
            $table->date('broken_date');
            $table->unsignedInteger('quantity');
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['employee_id', 'broken_date']);

            $table->foreign('buyer_id')->references('id')->on('inv_buyers')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('department_id')->references('id')->on('inv_departments')->nullOnDelete();
            $table->foreign('machine_id')->references('id')->on('inv_machines')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_broken_needles');
    }
};
