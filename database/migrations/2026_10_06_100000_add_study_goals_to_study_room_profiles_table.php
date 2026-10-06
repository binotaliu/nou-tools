<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * `weekly_goal_minutes` and `daily_goals` are independent: a student may
     * set either or both. `daily_goals` is keyed by ISO weekday (1 = Monday)
     * and each entry holds a `minutes` target and a `remind_at` time, either
     * of which may be missing, because the reminder time is per weekday.
     *
     * `notify_on_goal_reminder` is its own opt-in, defaulting to off: the
     * browser's single push subscription is shared with the class and
     * timer-end notifications, so subscribing for one is not wanting another.
     *
     * `goal_reminder_sent_on` is the Taipei date of the last reminder, so the
     * minute-by-minute sweep sends at most one per day.
     */
    public function up(): void
    {
        Schema::table('study_room_profiles', function (Blueprint $table) {
            $table->unsignedSmallInteger('weekly_goal_minutes')->nullable()->after('notify_on_timer_end');
            $table->json('daily_goals')->nullable()->after('weekly_goal_minutes');
            $table->boolean('notify_on_goal_reminder')->default(false)->after('daily_goals');
            $table->date('goal_reminder_sent_on')->nullable()->after('notify_on_goal_reminder');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('study_room_profiles', function (Blueprint $table) {
            $table->dropColumn(['weekly_goal_minutes', 'daily_goals', 'notify_on_goal_reminder', 'goal_reminder_sent_on']);
        });
    }
};
