<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cross-package 360° History support (MerchandisingTrace) — soft
     * references only, no DB-level FK constraint, since the referenced
     * tables (mer_styles, mer_sales_contract_pos, mer_buyers) live in a
     * separate package that may not always be installed alongside this one.
     */
    private array $tables = [
        'inv_requisitions',
        'inv_issues',
        'inv_production_consumptions',
        'inv_finished_goods_receives',
        'inv_grns',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                // Soft reference to mer_styles.id (MerchandisingTrace package).
                $table->unsignedBigInteger('mer_style_id')->nullable();
                // Soft reference to mer_sales_contract_pos.id (MerchandisingTrace package).
                $table->unsignedBigInteger('mer_sales_contract_po_id')->nullable();
                // Soft reference to mer_buyers.id (MerchandisingTrace package).
                $table->unsignedBigInteger('mer_buyer_id')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn(['mer_style_id', 'mer_sales_contract_po_id', 'mer_buyer_id']);
            });
        }
    }
};
