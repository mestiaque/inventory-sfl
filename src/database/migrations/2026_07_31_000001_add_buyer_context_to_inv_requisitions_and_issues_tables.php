<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Lets a floor section (Cutting/Sewing/...) state which buyer's order
        // a requisition is for, mirroring the same buyer/style/order_ref
        // pattern already used on inv_finished_goods_receives.
        Schema::table('inv_requisitions', function (Blueprint $table) {
            $table->foreignId('buyer_id')->nullable()->after('department_id')->constrained('inv_buyers')->nullOnDelete();
            $table->string('style')->nullable()->after('buyer_id');
            $table->string('order_ref')->nullable()->after('style');
        });

        Schema::table('inv_issues', function (Blueprint $table) {
            $table->foreignId('buyer_id')->nullable()->after('department_id')->constrained('inv_buyers')->nullOnDelete();
            $table->string('style')->nullable()->after('buyer_id');
            $table->string('order_ref')->nullable()->after('style');
        });
    }

    public function down(): void
    {
        Schema::table('inv_requisitions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('buyer_id');
            $table->dropColumn(['style', 'order_ref']);
        });

        Schema::table('inv_issues', function (Blueprint $table) {
            $table->dropConstrainedForeignId('buyer_id');
            $table->dropColumn(['style', 'order_ref']);
        });
    }
};
