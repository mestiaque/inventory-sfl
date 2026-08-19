<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Captured per received line (not per item) — the same item can arrive in
 * different lots on different GRNs with different expiry dates, so this has
 * to live alongside lot_no/batch_no rather than on inv_items itself.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inv_grn_items', function (Blueprint $table) {
            $table->date('expiry_date')->nullable()->after('batch_no');
        });
    }

    public function down(): void
    {
        Schema::table('inv_grn_items', function (Blueprint $table) {
            $table->dropColumn('expiry_date');
        });
    }
};
