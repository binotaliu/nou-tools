<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This table deliberately co-locates the seat *definition* (floor, kind,
     * group, seat number, code, label) with its live *occupancy* (who is
     * sitting there, what they're studying, their running timer). Keeping
     * both in one row means claiming an empty seat is a single race-safe
     * conditional UPDATE ("claim this seat WHERE student_schedule_id IS
     * NULL") instead of a separate insert/lookup against a second table.
     *
     * `student_schedule_id` is nullable-unique: NULL means "unoccupied", and
     * every supported database (including SQLite) treats multiple NULLs in
     * a unique column as distinct, so any number of seats can be empty at
     * once. Once a student claims a seat, the unique constraint guarantees
     * they can never simultaneously hold a second one.
     */
    public function up(): void
    {
        Schema::create('study_room_seats', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('floor');
            $table->string('kind', 16);
            $table->string('group_code', 16)->nullable();
            $table->unsignedTinyInteger('seat_number');
            $table->string('code', 32);
            $table->string('label');
            $table->foreignId('student_schedule_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('occupied_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->string('activity_verb', 32)->nullable();
            $table->foreignId('subject_course_id')->nullable()->constrained('courses')->nullOnDelete();
            $table->string('subject_label')->nullable();
            $table->string('timer_mode', 16)->nullable();
            $table->string('timer_phase', 16)->nullable();
            $table->timestamp('timer_started_at')->nullable();
            $table->timestamp('timer_ends_at')->nullable();
            $table->timestamps();

            $table->unique('code');
            $table->unique('student_schedule_id');
            $table->index(['floor', 'seat_number']);
            $table->index('last_seen_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('study_room_seats');
    }
};
