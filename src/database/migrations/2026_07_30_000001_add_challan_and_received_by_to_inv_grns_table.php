<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inv_grns', function (Blueprint $table) {
            $table->string('challan_invoice_no')->nullable()->after('supplier_id');
            $table->foreignId('received_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('inv_grns', function (Blueprint $table) {
            $table->dropConstrainedForeignId('received_by');
            $table->dropColumn('challan_invoice_no');
        });
    }
};
