<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Matches the "Requisition For" checkbox row on the printed requisition
     * form (Fabrics / Accessories / MachineParts / Equipment / Stationery).
     */
    public function up(): void
    {
        Schema::table('inv_requisitions', function (Blueprint $table) {
            $table->enum('requisition_for', ['fabrics', 'accessories', 'machine_parts', 'equipment', 'stationery'])
                ->nullable()->after('department_id');
        });
    }

    public function down(): void
    {
        Schema::table('inv_requisitions', function (Blueprint $table) {
            $table->dropColumn('requisition_for');
        });
    }
};
