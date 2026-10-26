<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_gate_pass_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('gate_pass_id');
            $table->unsignedBigInteger('item_id');
            $table->decimal('quantity', 15, 4);
            $table->unsignedInteger('carton_qty')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->foreign('gate_pass_id')->references('id')->on('inv_gate_passes')->cascadeOnDelete();
            $table->foreign('item_id')->references('id')->on('inv_items')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_gate_pass_items');
    }
};
