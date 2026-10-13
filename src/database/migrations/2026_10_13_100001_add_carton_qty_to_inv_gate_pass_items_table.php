<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Carton count per line on the printed shipment delivery challan (CTN column). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inv_gate_pass_items', function (Blueprint $table) {
            $table->unsignedInteger('carton_qty')->nullable()->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('inv_gate_pass_items', function (Blueprint $table) {
            $table->dropColumn('carton_qty');
        });
    }
};
