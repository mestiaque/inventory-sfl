<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Who the requisitioned material is meant for, selected at creation —
     * distinct from requested_by (who filled the form) and approved_by (who
     * authorized it). Matches the printed form's Issued by / Received by /
     * Authorized By signature blocks.
     */
    public function up(): void
    {
        Schema::table('inv_requisitions', function (Blueprint $table) {
            $table->foreignId('received_by')->nullable()->after('requested_by')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('inv_requisitions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('received_by');
        });
    }
};
