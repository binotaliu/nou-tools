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
        Schema::create('music_playlist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('music_playlist_id')->constrained()->cascadeOnDelete();
            $table->foreignId('music_track_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['music_playlist_id', 'music_track_id']);
            $table->index(['music_playlist_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('music_playlist_items');
    }
};
