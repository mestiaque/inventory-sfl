<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_issues', function (Blueprint $table) {
            $table->id();
            $table->string('issue_no')->unique();
            $table->foreignId('requisition_id')->nullable()->constrained('inv_requisitions')->nullOnDelete();
            $table->foreignId('store_id')->constrained('inv_stores')->restrictOnDelete();
            // Optional destination floor store (Cutting/Sewing/Finishing) — see
            // InvIssueObserver: issuing to a floor department posts a paired
            // qty_out(store_id)/qty_in(to_store_id) so Production Consumption
            // has a real balance to draw down.
            $table->foreignId('to_store_id')->nullable()->constrained('inv_stores')->nullOnDelete();
            $table->foreignId('department_id')->constrained('inv_departments')->restrictOnDelete();
            $table->date('issue_date');
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('department_received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('department_received_at')->nullable();
            $table->enum('department_receive_status', ['pending', 'partial', 'full'])->default('pending');
            $table->text('department_receive_remarks')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_issues');
    }
};
