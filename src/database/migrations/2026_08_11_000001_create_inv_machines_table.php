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
            $table->string('code')->unique();
            $table->string('model')->nullable();
            $table->string('origin')->nullable();
            $table->string('type')->nullable();
            $table->string('color')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('department_id')->nullable()->constrained('inv_departments')->nullOnDelete();
            $table->string('section')->nullable();
            $table->string('line')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_machines');
    }
};
