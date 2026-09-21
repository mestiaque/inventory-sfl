<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Store Receive + Price -> Receive Approval -> Stock Update": a
     * purchase-sourced GRN now waits for a Receive Approval before its
     * quantities post to the stock ledger (mirrors the Purchase
     * Requisition/Store Requisition dedicated-approval-page pattern).
     * Buyer-supplied GRNs are unaffected — they keep posting immediately
     * (InvGrnController still sets status='posted' for them directly), so
     * every row that could exist before this migration (only ever 'posted'
     * or 'cancelled') is untouched and still valid.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE inv_grns MODIFY status ENUM('pending', 'posted', 'rejected', 'cancelled') NOT NULL DEFAULT 'posted'");

        Schema::table('inv_grns', function (Blueprint $table) {
            $table->foreignId('approved_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->text('approval_remarks')->nullable()->after('approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('inv_grns', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn(['approved_at', 'approval_remarks']);
        });

        DB::statement("ALTER TABLE inv_grns MODIFY status ENUM('posted', 'cancelled') NOT NULL DEFAULT 'posted'");
    }
};
