<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * A student's own pomodoro cycle (focus / short break / long break /
     * rounds per cycle) lives on their profile so it follows them between
     * visits; NULL means "use the config default". The seat only records
     * which round of the cycle its running timer is in, so the break that
     * follows can be the short or the long one.
     */
    public function up(): void
    {
        Schema::table('study_room_profiles', function (Blueprint $table) {
            $table->unsignedSmallInteger('pomodoro_focus_minutes')->nullable()->after('emoji');
            $table->unsignedSmallInteger('pomodoro_short_break_minutes')->nullable()->after('pomodoro_focus_minutes');
            $table->unsignedSmallInteger('pomodoro_long_break_minutes')->nullable()->after('pomodoro_short_break_minutes');
            $table->unsignedTinyInteger('pomodoro_rounds_per_cycle')->nullable()->after('pomodoro_long_break_minutes');
        });

        Schema::table('study_room_seats', function (Blueprint $table) {
            $table->unsignedTinyInteger('timer_round')->nullable()->after('timer_phase');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('study_room_seats', function (Blueprint $table) {
            $table->dropColumn('timer_round');
        });

        Schema::table('study_room_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'pomodoro_focus_minutes',
                'pomodoro_short_break_minutes',
                'pomodoro_long_break_minutes',
                'pomodoro_rounds_per_cycle',
            ]);
        });
    }
};
