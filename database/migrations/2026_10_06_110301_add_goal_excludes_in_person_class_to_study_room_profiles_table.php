<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Defaults to on: in-person class hours are time spent in a classroom,
     * not self-study, so goals leave them out unless the student opts back in.
     */
    public function up(): void
    {
        Schema::table('study_room_profiles', function (Blueprint $table) {
            $table->boolean('goal_excludes_in_person_class')->default(true)->after('daily_goals');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('study_room_profiles', function (Blueprint $table) {
            $table->dropColumn('goal_excludes_in_person_class');
        });
    }
};
