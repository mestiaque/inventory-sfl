<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Barcode is opt-in per item (small/loose-piece items usually skip it) —
 * `inv_items.barcode` itself already exists (nullable, unique); this flag
 * just controls whether the Generate Barcode action is offered for that
 * item at all.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inv_items', function (Blueprint $table) {
            $table->boolean('barcode_enabled')->default(false)->after('barcode');
        });
    }

    public function down(): void
    {
        Schema::table('inv_items', function (Blueprint $table) {
            $table->dropColumn('barcode_enabled');
        });
    }
};
