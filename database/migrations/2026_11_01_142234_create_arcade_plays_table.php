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
        // `result` is a snapshot of what the player paid for, so tuning a game's
        // algorithm later never rewrites an earlier result. `played_on` is a
        // plain Taipei `Y-m-d` string, like acorn_transactions.earned_on.
        Schema::create('arcade_plays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_schedule_id')->constrained()->cascadeOnDelete();
            $table->string('game', 32);
            $table->string('choice', 32);
            $table->string('played_on', 10);
            $table->json('result');
            $table->foreignId('acorn_transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->unique(['student_schedule_id', 'game', 'played_on', 'choice']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('arcade_plays');
    }
};
