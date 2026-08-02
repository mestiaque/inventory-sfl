<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A buyer can supply fabric/accessories directly (common in RMG —
     * "buyer-supplied" or "CM" materials) that go straight into the store
     * without ever being locally purchased: no PO, no supplier. source_type
     * distinguishes this from a normal supplier purchase; supplier_id is
     * only required for the latter, so it has to become nullable here.
     */
    public function up(): void
    {
        Schema::table('inv_grns', function (Blueprint $table) {
            $table->enum('source_type', ['purchase', 'buyer_supplied'])->default('purchase')->after('purchase_order_id');
            $table->foreignId('buyer_id')->nullable()->after('supplier_id')->constrained('inv_buyers')->nullOnDelete();
            $table->string('style')->nullable()->after('buyer_id');
            $table->string('order_ref')->nullable()->after('style');
        });

        // ->change() would need doctrine/dbal, which isn't installed here;
        // MySQL lets you MODIFY a column's nullability directly without
        // touching its existing foreign key.
        DB::statement('ALTER TABLE inv_grns MODIFY supplier_id BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE inv_grns MODIFY supplier_id BIGINT UNSIGNED NOT NULL');

        Schema::table('inv_grns', function (Blueprint $table) {
            $table->dropConstrainedForeignId('buyer_id');
            $table->dropColumn(['source_type', 'style', 'order_ref']);
        });
    }
};
