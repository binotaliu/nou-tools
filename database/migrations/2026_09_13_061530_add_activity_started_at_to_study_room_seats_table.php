<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * `activity_started_at` marks when the seat's *current activity*
     * segment began, separately from `timer_started_at` (the current
     * Focus/Break phase's start, which also drives the countdown progress
     * bar). They coincide until `ChangeStudyActivity` moves only this one
     * forward — letting a mid-timer activity switch close out a session for
     * the elapsed segment without disturbing the round's own progress bar
     * or countdown, which stay anchored to `timer_started_at`.
     */
    public function up(): void
    {
        Schema::table('study_room_seats', function (Blueprint $table) {
            $table->timestamp('activity_started_at')->nullable()->after('timer_started_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('study_room_seats', function (Blueprint $table) {
            $table->dropColumn('activity_started_at');
        });
    }
};
