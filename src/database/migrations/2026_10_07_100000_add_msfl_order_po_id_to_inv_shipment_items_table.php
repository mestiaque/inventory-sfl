<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which Merchandising v2 PO (msfl_order_pos) each shipped line belongs to — gives
 * v2 the shipped qty / value (Line Wise Output report). Soft reference, no FK.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inv_shipment_items', function (Blueprint $table) {
            $table->unsignedBigInteger('msfl_order_po_id')->nullable()->after('item_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('inv_shipment_items', function (Blueprint $table) {
            $table->dropIndex(['msfl_order_po_id']);
            $table->dropColumn('msfl_order_po_id');
        });
    }
};
