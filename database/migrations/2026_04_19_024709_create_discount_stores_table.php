<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('discount_stores', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('status')->default('pending')->index();
            $table->string('type');
            $table->foreignId('category_id')->constrained('discount_store_categories');
            $table->string('city', 50)->nullable();
            $table->string('district', 50)->nullable();
            $table->string('address')->default('');
            $table->string('verification_method')->default('');
            $table->text('discount_details');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discount_stores');
    }
};
