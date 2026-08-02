<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_production_consumptions', function (Blueprint $table) {
            $table->id();
            $table->string('consumption_no')->unique();
            $table->foreignId('department_id')->constrained('inv_departments')->restrictOnDelete();
            $table->foreignId('issue_id')->nullable()->constrained('inv_issues')->nullOnDelete();
            $table->string('style')->nullable();
            $table->string('order_ref')->nullable();
            $table->date('consumption_date');
            $table->foreignId('store_id')->constrained('inv_stores')->restrictOnDelete();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_production_consumptions');
    }
};
