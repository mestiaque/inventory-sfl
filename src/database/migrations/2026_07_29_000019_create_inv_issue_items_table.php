<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_issue_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issue_id')->constrained('inv_issues')->cascadeOnDelete();
            $table->foreignId('requisition_item_id')->nullable()->constrained('inv_requisition_items')->nullOnDelete();
            $table->foreignId('item_id')->constrained('inv_items')->restrictOnDelete();
            $table->decimal('issued_qty', 15, 4);
            $table->decimal('department_received_qty', 15, 4)->default(0);
            $table->decimal('unit_rate', 15, 2)->default(0);
            $table->decimal('amount', 15, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_issue_items');
    }
};
