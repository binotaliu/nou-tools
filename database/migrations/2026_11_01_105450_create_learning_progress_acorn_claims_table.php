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
        // A claim exists even when the daily cap paid nothing, which is what
        // makes an item pay once ever: un-ticking and re-ticking earns nothing.
        Schema::create('learning_progress_acorn_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_schedule_id')->constrained()->cascadeOnDelete();
            $table->string('term', 16);
            $table->string('item_key', 64);
            $table->foreignId('acorn_transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->unique(['student_schedule_id', 'term', 'item_key'], 'lp_acorn_claims_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('learning_progress_acorn_claims');
    }
};
