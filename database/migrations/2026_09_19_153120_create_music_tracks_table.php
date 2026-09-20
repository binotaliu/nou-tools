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
        Schema::create('music_tracks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('author');
            $table->string('license');
            $table->string('license_url', 2048)->nullable();
            $table->string('source_url', 2048)->nullable();
            $table->string('mp3_path');
            $table->string('ogg_path');
            $table->unsignedInteger('duration_seconds');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('music_tracks');
    }
};
