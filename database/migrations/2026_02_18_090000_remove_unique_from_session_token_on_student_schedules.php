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
            // drop the unique constraint so multiple schedules can belong to the same session
            $table->dropUnique(['session_token']);

            // add a non-unique index for lookups (optional)
            $table->index('session_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_schedules', function (Blueprint $table) {
            $table->dropIndex(['session_token']);
            $table->unique('session_token');
        });
    }
};
