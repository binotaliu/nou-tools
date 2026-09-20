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
     * Every existing subscriber keeps their reminders: until now a
     * subscription could only have come from the schedule page's toggle,
     * because the study-room notification has not been deployed yet.
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
