<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inv_purchase_orders', function (Blueprint $table) {
            $table->foreignId('purchase_requisition_id')->nullable()->after('po_number')
                ->constrained('inv_purchase_requisitions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('inv_purchase_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('purchase_requisition_id');
        });
    }
};
