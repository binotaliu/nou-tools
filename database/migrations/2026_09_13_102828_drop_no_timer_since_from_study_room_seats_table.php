<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('study_room_seats', function (Blueprint $table) {
            $table->dropColumn('no_timer_since');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('study_room_seats', function (Blueprint $table) {
            $table->timestamp('no_timer_since')->nullable()->after('timer_ends_at');
        });
    }
};
