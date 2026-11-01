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
        // One report per session, kept as a hash like newsletter reactions.
        Schema::create('cafe_sticky_note_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cafe_sticky_note_id')->constrained()->cascadeOnDelete();
            $table->string('session_hash', 64);
            $table->timestamps();

            $table->unique(['cafe_sticky_note_id', 'session_hash'], 'cafe_note_reports_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cafe_sticky_note_reports');
    }
};
