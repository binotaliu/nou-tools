<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * One row per (issue, session): readers have no account, so the session
     * is the identity and the unique key is what limits a session to a
     * single reaction per issue. `session_hash` is a hash of the session id,
     * never the id itself.
     */
    public function up(): void
    {
        Schema::create('newsletter_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('newsletter_issue_id')->constrained()->cascadeOnDelete();
            $table->string('session_hash', 64);
            $table->string('reaction');
            $table->timestamps();

            $table->unique(['newsletter_issue_id', 'session_hash']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('newsletter_reactions');
    }
};
