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
        Schema::create('cafe_sticky_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_schedule_id')->constrained()->cascadeOnDelete();
            $table->string('text', 120)->nullable();
            $table->string('drawing_path')->nullable();
            $table->string('paper_color', 16)->default('yellow');
            // Position on the wall as a fraction of its width/height.
            $table->float('x')->default(0.5);
            $table->float('y')->default(0.5);
            $table->smallInteger('rotation')->default(0);
            $table->string('status', 24)->index();
            $table->json('ai_verdict')->nullable();
            $table->unsignedSmallInteger('report_count')->default(0);
            $table->foreignId('acorn_transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'published_at']);
            $table->index(['student_schedule_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cafe_sticky_notes');
    }
};
