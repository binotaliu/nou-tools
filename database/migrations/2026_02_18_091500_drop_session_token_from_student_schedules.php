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
        if (Schema::hasColumn('student_schedules', 'session_token')) {
            Schema::table('student_schedules', function (Blueprint $table) {
                // drop any index on session_token, then the column itself
                $table->dropIndex(['session_token']);
                $table->dropColumn('session_token');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_schedules', function (Blueprint $table) {
            $table->string('session_token')->index();
        });
    }
};
