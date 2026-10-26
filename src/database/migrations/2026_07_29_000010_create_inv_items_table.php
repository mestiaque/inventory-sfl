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
            $table->string('item_code');
            $table->string('item_name');
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->unsignedBigInteger('buyer_id')->nullable();
            $table->unsignedBigInteger('sub_category_id')->nullable();
            $table->unsignedBigInteger('unit_id');
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->unsignedBigInteger('color_id')->nullable();
            $table->unsignedBigInteger('size_id')->nullable();
            $table->text('specification')->nullable();
            $table->enum('item_type', ['raw_material', 'wip', 'finished_good'])->default('raw_material');
            $table->unsignedBigInteger('msfl_style_id')->nullable();
            $table->decimal('minimum_stock', 15, 4)->default(0.0000);
            $table->decimal('maximum_stock', 15, 4)->default(0.0000);
            $table->decimal('opening_stock', 15, 4)->default(0.0000);
            $table->decimal('opening_value', 15, 2)->default(0.00);
            $table->unsignedBigInteger('opening_store_id')->nullable();
            $table->string('barcode')->nullable();
            $table->boolean('barcode_enabled')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique('barcode');
            $table->index(['category_id', 'item_type']);
            $table->unique('item_code');
            $table->index('msfl_style_id');

            $table->foreign('brand_id')->references('id')->on('inv_brands')->nullOnDelete();
            $table->foreign('buyer_id')->references('id')->on('inv_buyers')->nullOnDelete();
            $table->foreign('category_id')->references('id')->on('inv_item_categories')->restrictOnDelete();
            $table->foreign('color_id')->references('id')->on('inv_colors')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('department_id')->references('id')->on('inv_departments')->nullOnDelete();
            $table->foreign('opening_store_id')->references('id')->on('inv_stores')->nullOnDelete();
            $table->foreign('size_id')->references('id')->on('inv_sizes')->nullOnDelete();
            $table->foreign('sub_category_id')->references('id')->on('inv_item_categories')->nullOnDelete();
            $table->foreign('supplier_id')->references('id')->on('inv_suppliers')->nullOnDelete();
            $table->foreign('unit_id')->references('id')->on('inv_units')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_items');
    }
};
