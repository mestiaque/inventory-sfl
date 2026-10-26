<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->string('address')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('products_supplied')->nullable();
            $table->unsignedSmallInteger('relation_since_year')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('tin_vat')->nullable();
            $table->unsignedTinyInteger('price_rating')->nullable();
            $table->unsignedTinyInteger('quality_rating')->nullable();
            $table->text('remarks')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique('code');

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_suppliers');
    }
};
