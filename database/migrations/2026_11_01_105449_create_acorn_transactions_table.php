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
        // The balance is never stored: it is the sum of `remaining` over
        // unexpired credits, so expiry needs no sweep and cannot drift.
        Schema::create('acorn_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_schedule_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 32);
            // Signed: positive credits, negative spends.
            $table->integer('amount');
            // Unspent part of a credit; null on spends.
            $table->unsignedInteger('remaining')->nullable();
            // Taipei date the credit counts towards for the caps.
            $table->date('earned_on');
            $table->timestamp('expires_at')->nullable();
            $table->nullableMorphs('source');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['student_schedule_id', 'expires_at']);
            $table->index(['student_schedule_id', 'kind', 'earned_on']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('acorn_transactions');
    }
};
