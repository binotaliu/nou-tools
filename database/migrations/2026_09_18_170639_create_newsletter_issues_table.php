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
        Schema::create('newsletter_issues', function (Blueprint $table) {
            $table->id();
            $table->string('issue_key')->unique();
            $table->date('publishes_on')->unique();
            $table->string('title')->nullable();
            $table->date('covers_from');
            $table->date('covers_to');
            $table->date('highlights_from');
            $table->date('highlights_to');
            $table->text('highlights_intro')->nullable();
            $table->json('highlights_events')->nullable();
            $table->string('status')->default('draft');
            $table->dateTime('published_at')->nullable();
            $table->dateTime('ai_drafted_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'publishes_on']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('newsletter_issues');
    }
};
