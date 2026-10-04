<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The Merchandising links on inventory documents now point at Merchandising
 * v2 (merchandising-sfl) — msfl_buyers, msfl_styles, msfl_order_pos — which
 * replaces the old MerchandisingTrace package. Soft references, no FK.
 *
 * Refuses to run if any old link is set: those ids belong to the old
 * package's tables and would point at the wrong v2 rows.
 */
return new class extends Migration
{
    private array $tables = ['inv_requisitions', 'inv_issues', 'inv_production_consumptions', 'inv_finished_goods_receives', 'inv_grns'];

    private array $renames = [
        'mer_buyer_id' => 'msfl_buyer_id',
        'mer_style_id' => 'msfl_style_id',
        'mer_sales_contract_po_id' => 'msfl_order_po_id',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            foreach (array_keys($this->renames) as $old) {
                if (Schema::hasColumn($table, $old) && DB::table($table)->whereNotNull($old)->exists()) {
                    throw new RuntimeException("{$table}.{$old} has values linked to the old Merchandising package — clear or map them first.");
                }
            }
        }

        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                foreach ($this->renames as $old => $new) {
                    if (Schema::hasColumn($table, $old)) {
                        $t->renameColumn($old, $new);
                    }
                }
            });
            Schema::table($table, function (Blueprint $t) {
                $t->index('msfl_style_id');
                $t->index('msfl_order_po_id');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropIndex(['msfl_style_id']);
                $t->dropIndex(['msfl_order_po_id']);
            });
            Schema::table($table, function (Blueprint $t) {
                foreach ($this->renames as $old => $new) {
                    $t->renameColumn($new, $old);
                }
            });
        }
    }
};
