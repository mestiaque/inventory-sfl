<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Brings the digital entry in line with the factory's paper "Daily Needle
 * Supply Report" slip: line no, needle type, needle size are written on
 * every row of that form, and buyer/style identify which order the sheet
 * was raised for (same buyer/style pattern already used on inv_requisitions,
 * inv_issues, inv_finished_goods_receives).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inv_broken_needles', function (Blueprint $table) {
            $table->string('line_no')->nullable()->after('machine_id');
            $table->string('needle_type')->nullable()->after('line_no');
            $table->string('needle_size')->nullable()->after('needle_type');
            $table->foreignId('buyer_id')->nullable()->after('needle_size')->constrained('inv_buyers')->nullOnDelete();
            $table->string('style')->nullable()->after('buyer_id');
        });
    }

    public function down(): void
    {
        Schema::table('inv_broken_needles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('buyer_id');
            $table->dropColumn(['line_no', 'needle_type', 'needle_size', 'style']);
        });
    }
};
