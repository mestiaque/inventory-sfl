<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inv_suppliers', function (Blueprint $table) {
            $table->string('products_supplied', 255)->nullable()->after('contact_person');
            $table->unsignedSmallInteger('relation_since_year')->nullable()->after('products_supplied');
            $table->unsignedTinyInteger('price_rating')->nullable()->after('tin_vat');
            $table->unsignedTinyInteger('quality_rating')->nullable()->after('price_rating');
        });
    }

    public function down(): void
    {
        Schema::table('inv_suppliers', function (Blueprint $table) {
            $table->dropColumn(['products_supplied', 'relation_since_year', 'price_rating', 'quality_rating']);
        });
    }
};
