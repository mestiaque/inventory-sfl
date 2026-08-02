<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * All three are optional context on the Item Master itself (matches the
     * real factory spreadsheet's Category/Supplier columns, plus which
     * department/section normally owns the item and — for buyer-specific
     * items — which buyer it belongs to).
     */
    public function up(): void
    {
        Schema::table('inv_items', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('category_id')->constrained('inv_departments')->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->after('department_id')->constrained('inv_suppliers')->nullOnDelete();
            $table->foreignId('buyer_id')->nullable()->after('supplier_id')->constrained('inv_buyers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('inv_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
            $table->dropConstrainedForeignId('supplier_id');
            $table->dropConstrainedForeignId('buyer_id');
        });
    }
};
