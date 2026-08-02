<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Store Delivery Challan approval chain: Prepared (created_by/issued_by,
     * at creation) -> Authorized -> Approved. Stock only leaves the store on
     * final approval (see InvIssueController@approve) — mirrors the Gate
     * Pass pending->issued pattern but with an extra authorization step to
     * match the challan's Prepared/Authorized/Approved signature blocks.
     */
    public function up(): void
    {
        Schema::table('inv_issues', function (Blueprint $table) {
            $table->enum('status', ['pending', 'authorized', 'approved'])->default('pending')->after('department_id');
            $table->foreignId('authorized_by')->nullable()->after('issued_by')->constrained('users')->nullOnDelete();
            $table->timestamp('authorized_at')->nullable()->after('authorized_by');
            $table->foreignId('approved_by')->nullable()->after('authorized_at')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
        });
    }

    public function down(): void
    {
        Schema::table('inv_issues', function (Blueprint $table) {
            $table->dropConstrainedForeignId('authorized_by');
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn(['status', 'authorized_at', 'approved_at']);
        });
    }
};
