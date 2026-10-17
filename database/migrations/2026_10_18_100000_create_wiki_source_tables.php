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
        Schema::create('wiki_source_documents', function (Blueprint $table) {
            $table->id();
            // The document's kind across semesters, e.g. course-selection-notes.
            $table->string('series', 60);
            // Semester such as 115-1; null for a document not tied to one
            // (versioned with effective_from / effective_to instead).
            $table->string('term', 10)->nullable();
            $table->string('title');
            $table->string('url', 2048);
            $table->string('status', 20)->default('pending');
            $table->text('failure_reason')->nullable();
            $table->unsignedSmallInteger('page_count')->nullable();
            $table->timestamp('fetched_at')->nullable();
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->timestamps();

            $table->unique(['series', 'term']);
            $table->index(['series', 'effective_to']);
        });

        Schema::create('wiki_source_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wiki_source_document_id')->constrained()->cascadeOnDelete();
            $table->string('key', 60);
            $table->string('title');
            $table->longText('body');
            $table->unsignedSmallInteger('page_start');
            $table->unsignedSmallInteger('page_end');
            $table->unsignedSmallInteger('position');
            // sha256 of body, to tell which sections a re-fetch changed.
            $table->char('content_hash', 64);
            $table->timestamps();

            $table->unique(['wiki_source_document_id', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wiki_source_sections');
        Schema::dropIfExists('wiki_source_documents');
    }
};
