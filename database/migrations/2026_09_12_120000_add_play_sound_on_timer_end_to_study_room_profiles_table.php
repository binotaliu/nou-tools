<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('study_room_profiles', function (Blueprint $table) {
            $table->boolean('play_sound_on_timer_end')->default(true)->after('pomodoro_rounds_per_cycle');
        });
    }

    public function down(): void
    {
        Schema::table('study_room_profiles', function (Blueprint $table) {
            $table->dropColumn('play_sound_on_timer_end');
        });
    }
};
