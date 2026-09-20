<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * A browser has a single push subscription that the study room's
     * timer-end notification shares with the class-starting reminders, and
     * the reminders used to go to every schedule that held one. Registering
     * a browser for the study room therefore also opted the student into
     * class reminders. `notify_on_class_start` is the reminders' own opt-in,
     * the counterpart of `study_room_profiles.notify_on_timer_end`.
     *
     * Existing subscribers keep their reminders, except schedules that have
     * already opted into the study-room notification: that shipped the same
     * day, so their subscription most likely came from the study room.
     */
    public function up(): void
    {
        Schema::table('student_schedules', function (Blueprint $table) {
            $table->boolean('notify_on_class_start')->default(false)->after('announcement_categories');
        });

        $subscriptionTable = config('webpush.table_name');

        DB::table('student_schedules')
            ->whereExists(fn ($query) => $query
                ->select(DB::raw(1))
                ->from($subscriptionTable)
                ->where("{$subscriptionTable}.subscribable_type", 'App\Models\StudentSchedule')
                ->whereColumn("{$subscriptionTable}.subscribable_id", 'student_schedules.id'))
            ->whereNotExists(fn ($query) => $query
                ->select(DB::raw(1))
                ->from('study_room_profiles')
                ->whereColumn('study_room_profiles.student_schedule_id', 'student_schedules.id')
                ->where('study_room_profiles.notify_on_timer_end', true))
            ->update(['notify_on_class_start' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_schedules', function (Blueprint $table) {
            $table->dropColumn('notify_on_class_start');
        });
    }
};
