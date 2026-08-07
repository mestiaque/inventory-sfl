<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Links an Operator/Store Incharge to their HR employee record in addition
 * to their system login (user_id). No FK constraint — hr_employees.id is
 * `int unsigned` while every other FK column in this package is `bigint
 * unsigned`, which errno-150s a real foreign key; app-level validation
 * (exists:hr_employees,id) covers integrity instead, same as
 * inv_requisitions.received_by.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inv_operators', function (Blueprint $table) {
            $table->unsignedBigInteger('employee_id')->nullable()->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('inv_operators', function (Blueprint $table) {
            $table->dropColumn('employee_id');
        });
    }
};
