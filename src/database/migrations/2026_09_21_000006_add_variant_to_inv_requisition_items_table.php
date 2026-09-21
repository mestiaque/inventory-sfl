<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inv_requisition_items', function (Blueprint $table) {
            $table->foreignId('color_id')->nullable()->after('item_id')->constrained('inv_colors')->nullOnDelete();
            $table->foreignId('size_id')->nullable()->after('color_id')->constrained('inv_sizes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('inv_requisition_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('color_id');
            $table->dropConstrainedForeignId('size_id');
        });
    }
};
