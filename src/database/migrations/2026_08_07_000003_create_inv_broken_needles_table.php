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
            $table->foreignId('department_id')->nullable()->constrained('inv_departments')->nullOnDelete();
            $table->date('broken_date');
            $table->unsignedInteger('quantity');
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['employee_id', 'broken_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_broken_needles');
    }
};
