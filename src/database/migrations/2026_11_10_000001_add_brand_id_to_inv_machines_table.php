<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Machine brand, picked from the Brands master. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('inv_machines', 'brand_id')) {
            Schema::table('inv_machines', function (Blueprint $table) {
                $table->unsignedBigInteger('brand_id')->nullable()->after('machine_no');
                $table->foreign('brand_id')->references('id')->on('inv_brands')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('inv_machines', 'brand_id')) {
            Schema::table('inv_machines', function (Blueprint $table) {
                $table->dropForeign(['brand_id']);
                $table->dropColumn('brand_id');
            });
        }
    }
};
