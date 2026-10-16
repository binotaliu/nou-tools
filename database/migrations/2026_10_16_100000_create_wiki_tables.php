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
        Schema::create('wiki_pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 60)->unique();
            // Points at wiki_revisions; the foreign key is added below, once that table exists.
            $table->unsignedBigInteger('current_revision_id')->nullable();
            $table->boolean('is_protected')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('wiki_slug_redirects', function (Blueprint $table) {
            $table->id();
            $table->string('old_slug', 60)->unique();
            $table->foreignId('wiki_page_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('wiki_edit_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('wiki_page_id')->nullable()->constrained()->nullOnDelete();
            $table->string('requested_slug', 60)->nullable();
            $table->string('requested_title')->nullable();
            $table->unsignedBigInteger('base_revision_id')->nullable();
            $table->text('instruction');
            $table->json('source_urls');
            $table->json('refinements');
            $table->foreignId('student_schedule_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_hash', 64);
            $table->string('status')->default('drafting');
            $table->string('draft_slug', 60)->nullable();
            $table->string('draft_title')->nullable();
            $table->text('draft_description')->nullable();
            $table->longText('draft_body')->nullable();
            $table->json('draft_categories')->nullable();
            $table->string('draft_edit_summary')->nullable();
            $table->json('ai_verdict')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->unsignedBigInteger('published_revision_id')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['student_schedule_id', 'created_at']);
        });

        Schema::create('wiki_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wiki_page_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_revision_id')->nullable()->constrained('wiki_revisions')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->longText('body');
            $table->json('categories');
            $table->string('edit_summary')->nullable();
            $table->string('origin');
            $table->foreignId('wiki_edit_request_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            // Which schedule's device asked for the edit, so the cooldown rule can tell editors apart.
            $table->foreignId('student_schedule_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->index(['wiki_page_id', 'id']);
        });

        Schema::table('wiki_pages', function (Blueprint $table) {
            $table->foreign('current_revision_id')->references('id')->on('wiki_revisions')->nullOnDelete();
        });

        Schema::table('wiki_edit_requests', function (Blueprint $table) {
            $table->foreign('base_revision_id')->references('id')->on('wiki_revisions')->nullOnDelete();
            $table->foreign('published_revision_id')->references('id')->on('wiki_revisions')->nullOnDelete();
        });

        Schema::create('wiki_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 40)->unique();
            $table->timestamps();
        });

        Schema::create('wiki_category_page', function (Blueprint $table) {
            $table->foreignId('wiki_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('wiki_page_id')->constrained()->cascadeOnDelete();

            $table->primary(['wiki_category_id', 'wiki_page_id']);
        });

        Schema::create('wiki_links', function (Blueprint $table) {
            $table->foreignId('from_page_id')->constrained('wiki_pages')->cascadeOnDelete();
            $table->string('to_slug', 60);

            $table->primary(['from_page_id', 'to_slug']);
            $table->index('to_slug');
        });

        Schema::table('student_schedules', function (Blueprint $table) {
            $table->dateTime('wiki_blocked_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_schedules', function (Blueprint $table) {
            $table->dropColumn('wiki_blocked_at');
        });

        Schema::table('wiki_pages', function (Blueprint $table) {
            $table->dropForeign(['current_revision_id']);
        });

        Schema::table('wiki_edit_requests', function (Blueprint $table) {
            $table->dropForeign(['base_revision_id']);
            $table->dropForeign(['published_revision_id']);
        });

        Schema::dropIfExists('wiki_links');
        Schema::dropIfExists('wiki_category_page');
        Schema::dropIfExists('wiki_categories');
        Schema::dropIfExists('wiki_revisions');
        Schema::dropIfExists('wiki_edit_requests');
        Schema::dropIfExists('wiki_slug_redirects');
        Schema::dropIfExists('wiki_pages');
    }
};
