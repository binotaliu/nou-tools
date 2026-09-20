<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Nothing server-side reacts to a countdown reaching zero today, so a
     * backgrounded or OS-killed tab is never told its timer ended. A
     * sub-minute sweep now pushes that notification, which needs two
     * columns.
     *
     * `notify_on_timer_end` is the student's opt-in, defaulting to off: a
     * browser has a single push subscription that the class reminders also
     * use, so wanting one is not wanting the other.
     *
     * `timer_end_notified_at` marks a seat as already told. It is required
     * rather than nice to have: a finished countdown keeps `timer_ends_at`
     * in the past while it runs into overtime, for up to
     * `study-room.heartbeat.idle_release_seconds`, so without the marker
     * every sweep would notify the same seat again.
     */
    public function up(): void
    {
        Schema::table('study_room_profiles', function (Blueprint $table) {
            $table->boolean('notify_on_timer_end')->default(false)->after('play_sound_on_timer_end');
        });

        Schema::table('study_room_seats', function (Blueprint $table) {
            $table->timestamp('timer_end_notified_at')->nullable()->after('paused_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('study_room_seats', function (Blueprint $table) {
            $table->dropColumn('timer_end_notified_at');
        });

        Schema::table('study_room_profiles', function (Blueprint $table) {
            $table->dropColumn('notify_on_timer_end');
        });
    }
};
