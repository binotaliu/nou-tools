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
        Schema::create('study_room_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_schedule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_course_id')->nullable()->constrained('courses')->nullOnDelete();
            $table->string('subject_label')->nullable();
            $table->string('activity_verb', 32)->nullable();
            $table->string('timer_mode', 16);
            $table->timestamp('started_at');
            $table->timestamp('ended_at');
            $table->unsignedInteger('focus_seconds');
            $table->boolean('was_completed')->default(false);
            $table->timestamps();

            $table->index('ended_at');
            $table->index(['student_schedule_id', 'ended_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('study_room_sessions');
    }
};
