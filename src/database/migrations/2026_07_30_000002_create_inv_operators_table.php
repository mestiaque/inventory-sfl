<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_operators', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            // operator: sees only their own created records.
            // store_incharge / store_manager: sees every record for their assigned store.
            $table->enum('designation', ['operator', 'store_incharge', 'store_manager'])->default('operator');
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('store_id')->nullable()->constrained('inv_stores')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_operators');
    }
};
