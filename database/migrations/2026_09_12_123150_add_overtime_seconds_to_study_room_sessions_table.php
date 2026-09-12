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
        Schema::table('study_room_sessions', function (Blueprint $table) {
            $table->unsignedInteger('overtime_seconds')->default(0)->after('focus_seconds');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('study_room_sessions', function (Blueprint $table) {
            $table->dropColumn('overtime_seconds');
        });
    }
};
