<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_purchase_requisitions', function (Blueprint $table) {
            $table->id();
            $table->string('requisition_no');
            $table->date('requisition_date');
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'converted', 'partially_converted'])->default('pending');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_remarks')->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique('requisition_no');

            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('department_id')->references('id')->on('inv_departments')->nullOnDelete();
            $table->foreign('requested_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_purchase_requisitions');
    }
};
