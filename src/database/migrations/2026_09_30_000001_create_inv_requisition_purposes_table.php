<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Requisition For" master (Fabrics, Accessories, …) — was a fixed list in
     * the code. inv_requisitions.requisition_for keeps storing the purpose's
     * `code`, so the five codes it already used are seeded here unchanged.
     */
    public function up(): void
    {
        Schema::create('inv_requisition_purposes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 50)->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        $now = now();
        DB::table('inv_requisition_purposes')->insert(collect([
            'fabrics' => 'Fabrics', 'accessories' => 'Accessories', 'machine_parts' => 'Machine Parts',
            'equipment' => 'Equipment', 'stationery' => 'Stationery',
        ])->map(fn ($name, $code) => ['name' => $name, 'code' => $code, 'sort_order' => 0, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now])
            ->values()->map(fn ($row, $i) => ['sort_order' => $i + 1] + $row)->all());
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_requisition_purposes');
    }
};
