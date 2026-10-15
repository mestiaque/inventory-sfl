<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A finished-good item belongs to one Merchandising style (made from the style on the first
 * Finish Store receive — MerchandisingLink::finishedGoodItem()). Soft reference, no FK.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inv_items', function (Blueprint $table) {
            $table->unsignedBigInteger('msfl_style_id')->nullable()->after('item_type')->index();
        });
    }

    public function down(): void
    {
        Schema::table('inv_items', function (Blueprint $table) {
            $table->dropIndex(['msfl_style_id']);
            $table->dropColumn('msfl_style_id');
        });
    }
};
