<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Goods a department took on a Store Issue and brings back unused — stock goes back into the issue's store.
        Schema::create('inv_issue_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_no')->unique();
            $table->foreignId('issue_id')->constrained('inv_issues')->restrictOnDelete();
            $table->foreignId('store_id')->constrained('inv_stores')->restrictOnDelete();
            $table->date('return_date');
            $table->text('reason')->nullable();
            $table->foreignId('returned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inv_issue_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_id')->constrained('inv_issue_returns')->cascadeOnDelete();
            $table->foreignId('issue_item_id')->constrained('inv_issue_items')->restrictOnDelete();
            $table->foreignId('item_id')->constrained('inv_items')->restrictOnDelete();
            $table->foreignId('color_id')->nullable()->constrained('inv_colors')->nullOnDelete();
            $table->foreignId('size_id')->nullable()->constrained('inv_sizes')->nullOnDelete();
            $table->decimal('returned_qty', 15, 4);
            $table->decimal('unit_rate', 15, 2)->default(0);
            $table->decimal('amount', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_issue_return_items');
        Schema::dropIfExists('inv_issue_returns');
    }
};
