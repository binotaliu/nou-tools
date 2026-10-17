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
        // The audit trail of the edit flow. Requests are pruned and rewritten
        // in place, so each row keeps its own snapshot of what it was about.
        Schema::create('wiki_edit_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wiki_edit_request_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event', 32)->index();
            $table->string('request_uuid', 36)->nullable()->index();
            $table->string('page_slug')->nullable();
            $table->string('page_title')->nullable();
            $table->text('instruction')->nullable();
            $table->foreignId('student_schedule_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_hash', 64)->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('detail')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wiki_edit_events');
    }
};
