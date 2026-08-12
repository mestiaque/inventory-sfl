<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The floor's own physical machine tag (e.g. "B.A", "B.St", "SN2") — distinct
 * from `code` (this system's generated MC-SW-xxx identifier) — is what gets
 * printed in the "Machine No" column on the Daily Broken Needle Report.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inv_machines', function (Blueprint $table) {
            $table->string('machine_no')->nullable()->after('code');
        });
    }

    public function down(): void
    {
        Schema::table('inv_machines', function (Blueprint $table) {
            $table->dropColumn('machine_no');
        });
    }
};
