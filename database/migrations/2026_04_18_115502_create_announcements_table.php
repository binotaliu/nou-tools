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
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('source_key');
            $table->string('source_name');
            $table->string('category');
            $table->string('source_id');
            $table->string('title');
            $table->text('url');
            $table->json('tags')->nullable();
            $table->dateTime('published_at')->nullable();
            $table->dateTime('fetched_at');
            $table->dateTime('expired_at')->nullable();
            $table->timestamps();

            $table->unique(['source_key', 'source_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
