<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which sample this challan is sending SFL's own copy of (PP/FIT/SIZESET/…,
 * from Merchandising's Sample Type master) — labels the "<TYPE> SAMPLE FROM
 * SFL" column instead of it always saying "P.P". Soft reference + a name
 * snapshot (same convention as the item/PO/style/colour snapshot on the
 * challan's lines), so a printed challan stays correct even if that sample
 * type is renamed later.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inv_sample_challans', function (Blueprint $table) {
            $table->unsignedBigInteger('msfl_sample_type_id')->nullable()->after('msfl_buyer_id')->index();
            $table->string('sample_type_name', 50)->nullable()->after('msfl_sample_type_id');
        });
    }

    public function down(): void
    {
        Schema::table('inv_sample_challans', function (Blueprint $table) {
            $table->dropColumn(['msfl_sample_type_id', 'sample_type_name']);
        });
    }
};
