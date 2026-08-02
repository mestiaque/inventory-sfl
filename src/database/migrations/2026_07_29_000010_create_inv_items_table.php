<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_items', function (Blueprint $table) {
            $table->id();
            $table->string('item_code')->unique();
            $table->string('item_name');
            $table->foreignId('category_id')->constrained('inv_item_categories')->restrictOnDelete();
            $table->foreignId('sub_category_id')->nullable()->constrained('inv_item_categories')->nullOnDelete();
            $table->foreignId('unit_id')->constrained('inv_units')->restrictOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained('inv_brands')->nullOnDelete();
            $table->foreignId('color_id')->nullable()->constrained('inv_colors')->nullOnDelete();
            $table->foreignId('size_id')->nullable()->constrained('inv_sizes')->nullOnDelete();
            $table->text('specification')->nullable();
            $table->enum('item_type', ['raw_material', 'wip', 'finished_good'])->default('raw_material');
            $table->decimal('minimum_stock', 15, 4)->default(0);
            $table->decimal('maximum_stock', 15, 4)->default(0);
            $table->decimal('opening_stock', 15, 4)->default(0);
            $table->decimal('opening_value', 15, 2)->default(0);
            $table->foreignId('opening_store_id')->nullable()->constrained('inv_stores')->nullOnDelete();
            $table->string('barcode')->nullable()->unique();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category_id', 'item_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_items');
    }
};
