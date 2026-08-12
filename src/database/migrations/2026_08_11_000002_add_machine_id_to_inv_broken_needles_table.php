<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inv_broken_needles', function (Blueprint $table) {
            $table->foreignId('machine_id')->nullable()->after('department_id')->constrained('inv_machines')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('inv_broken_needles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('machine_id');
        });
    }
};
