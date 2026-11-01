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
        // The name and emoji are copied from the menu item, so editing or
        // removing a menu item never rewrites what someone bought.
        Schema::create('cafe_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_schedule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cafe_menu_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 32);
            $table->string('emoji', 16);
            $table->string('cup_style', 16)->default('mug');
            $table->boolean('to_go')->default(false);
            $table->unsignedSmallInteger('price_paid');
            $table->foreignId('acorn_transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['student_schedule_id', 'expires_at']);
            $table->index(['student_schedule_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cafe_orders');
    }
};
