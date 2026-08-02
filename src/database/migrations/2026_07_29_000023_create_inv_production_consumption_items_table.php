<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_production_consumption_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consumption_id')->constrained('inv_production_consumptions')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('inv_items')->restrictOnDelete();
            $table->decimal('consumed_qty', 15, 4);
            $table->decimal('waste_qty', 15, 4)->default(0);
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_production_consumption_items');
    }
};
