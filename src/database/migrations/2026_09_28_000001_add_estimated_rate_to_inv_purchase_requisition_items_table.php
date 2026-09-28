<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Estimated / market price per unit, given when a Purchase Requisition
     * is raised — what the approver signs off on. The actual price is only
     * known later and is entered at GRN (store receive) time.
     */
    public function up(): void
    {
        Schema::table('inv_purchase_requisition_items', function (Blueprint $table) {
            $table->decimal('estimated_rate', 15, 2)->nullable()->after('requested_qty');
        });
    }

    public function down(): void
    {
        Schema::table('inv_purchase_requisition_items', function (Blueprint $table) {
            $table->dropColumn('estimated_rate');
        });
    }
};
