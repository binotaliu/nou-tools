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
        Schema::create('newsletter_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('newsletter_issue_id')->constrained()->cascadeOnDelete();
            $table->foreignId('announcement_id')->nullable()->constrained()->nullOnDelete();
            $table->string('section');
            $table->string('source_name');
            $table->text('url')->nullable();
            $table->string('headline');
            $table->text('summary')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['newsletter_issue_id', 'section', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('newsletter_items');
    }
};
