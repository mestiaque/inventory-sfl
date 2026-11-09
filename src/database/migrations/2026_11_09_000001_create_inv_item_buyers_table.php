<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * An item can belong to several buyers. inv_items.buyer_id is left as it is
 * (it keeps the first picked buyer for older screens); the full list lives
 * here. Existing items' buyer_id is copied in — nothing on inv_items changes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_item_buyers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('buyer_id');
            $table->timestamps();
            $table->unique(['item_id', 'buyer_id']);
            $table->foreign('item_id')->references('id')->on('inv_items')->cascadeOnDelete();
            $table->foreign('buyer_id')->references('id')->on('inv_buyers')->cascadeOnDelete();
        });

        DB::statement('INSERT INTO inv_item_buyers (item_id, buyer_id, created_at, updated_at)
            SELECT i.id, i.buyer_id, NOW(), NOW() FROM inv_items i
            JOIN inv_buyers b ON b.id = i.buyer_id
            WHERE i.buyer_id IS NOT NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_item_buyers');
    }
};
