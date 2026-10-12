<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_sample_challan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sample_challan_id')->constrained('inv_sample_challans')->cascadeOnDelete();
            $table->unsignedInteger('sequence')->default(0);
            // Soft reference (ME\MerchandisingSfl\Models\OrderPo) — no FK, same
            // convention as the other inventory documents' msfl_order_po_id.
            $table->unsignedBigInteger('msfl_order_po_id')->nullable()->index();
            // Snapshotted at save time from the picked PO (item_name = style
            // name) so the printed challan stays correct even if that PO,
            // style or colour is edited/removed in Merchandising later.
            $table->string('item_name')->nullable();
            $table->string('po_no')->nullable();
            $table->string('style_no')->nullable();
            $table->string('color_name')->nullable();
            $table->foreignId('size_id')->nullable()->constrained('inv_sizes')->nullOnDelete();
            $table->unsignedInteger('buyer_sample_qty')->default(0);
            $table->unsignedInteger('pp_sample_qty')->default(0);
            $table->string('unit', 20)->default('Pcs');
            $table->string('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_sample_challan_items');
    }
};
