<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * `no_timer_since` marks when the seat's current "no active timer"
     * stretch began — set on claim and whenever a running timer stops, and
     * cleared whenever a timer starts. `timer_started_at` alone can't drive
     * the no-timer grace sweep (`ReleaseSeatsWithoutTimer`): it goes back to
     * null every time a timer stops, even after the student already studied
     * for hours, which would make a stale `occupied_at` look like "never
     * started a timer" the moment they stop one.
     */
    public function up(): void
    {
        Schema::table('study_room_seats', function (Blueprint $table) {
            $table->timestamp('no_timer_since')->nullable()->after('timer_ends_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('study_room_seats', function (Blueprint $table) {
            $table->dropColumn('no_timer_since');
        });
    }
};
