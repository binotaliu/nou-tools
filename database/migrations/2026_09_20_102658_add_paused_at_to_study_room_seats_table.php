<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * `paused_at` is set while a Focus-phase timer is paused and null
     * otherwise. Pausing records the elapsed segment as a session (like an
     * activity change does) but leaves `timer_started_at`/`timer_ends_at`
     * alone, so the progress bar stays frozen at where it was; resuming
     * shifts both forward by the paused duration and starts a new segment.
     */
    public function up(): void
    {
        Schema::table('study_room_seats', function (Blueprint $table) {
            $table->timestamp('paused_at')->nullable()->after('timer_ends_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('study_room_seats', function (Blueprint $table) {
            $table->dropColumn('paused_at');
        });
    }
};
