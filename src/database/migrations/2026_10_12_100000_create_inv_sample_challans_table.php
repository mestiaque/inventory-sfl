<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_sample_challans', function (Blueprint $table) {
            $table->id();
            $table->string('challan_no', 50)->unique();
            $table->date('challan_date');
            $table->foreignId('store_id')->nullable()->constrained('inv_stores')->nullOnDelete();
            // Merchandising v2 buyer — soft reference, no FK (see
            // 2026_10_04_000001_link_inventory_documents_to_merchandising_v2).
            $table->unsignedBigInteger('msfl_buyer_id')->nullable()->index();
            // Receiver block — defaults from the buyer but overridable (e.g. a
            // specific buying-house contact that isn't the buyer record itself).
            $table->string('to_name')->nullable();
            $table->text('to_address')->nullable();
            $table->string('to_contact', 100)->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_sample_challans');
    }
};
