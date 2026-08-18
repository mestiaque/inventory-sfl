<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * "Received By" now records which HR employee physically received the
 * challan, not which system User account was logged in — a warehouse/store
 * staff member usually isn't a login-having User. hr_employees.id is `int
 * unsigned` while this column is `bigint unsigned` (foreignId), so — same
 * as inv_broken_needles.employee_id — a real FK isn't possible; the values
 * are still validated at the app level (see InvGrnRequest).
 */
return new class extends Migration
{
    public function up(): void
    {
        // This connection's FK constraints don't always match what the
        // migrations that created them declared (a pre-existing gap in this
        // database, unrelated to this change) — check information_schema
        // rather than assuming the constraint is actually there to drop.
        $exists = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'inv_grns')
            ->where('CONSTRAINT_NAME', 'inv_grns_received_by_foreign')
            ->exists();

        if ($exists) {
            Schema::table('inv_grns', function (Blueprint $table) {
                $table->dropForeign(['received_by']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('inv_grns', function (Blueprint $table) {
            $table->foreign('received_by')->references('id')->on('users')->nullOnDelete();
        });
    }
};
