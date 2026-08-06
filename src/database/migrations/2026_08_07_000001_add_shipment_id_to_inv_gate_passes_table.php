<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Flips the Shipment <-> Gate Pass relationship to match real-world
 * warehouse practice: Shipment is entered first (what/how much/for whom,
 * invoice/packing list), then a Gate Pass is issued against it to let the
 * goods physically leave through the gate. The gate pass is still the
 * actual stock-out event — this migration only adds the forward reference
 * so a gate pass can be created against an existing shipment.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inv_gate_passes', function (Blueprint $table) {
            $table->foreignId('shipment_id')->nullable()->after('id')->constrained('inv_shipments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('inv_gate_passes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('shipment_id');
        });
    }
};
