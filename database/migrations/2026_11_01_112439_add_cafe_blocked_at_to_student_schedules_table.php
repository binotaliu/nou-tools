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
        Schema::table('student_schedules', function (Blueprint $table) {
            // Set from Filament: this schedule can no longer post sticky notes.
            $table->timestamp('cafe_blocked_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_schedules', function (Blueprint $table) {
            $table->dropColumn('cafe_blocked_at');
        });
    }
};
