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
        Schema::table('course_classes', function (Blueprint $table) {
            $table->unique(['course_id', 'code', 'type'], 'course_classes_unique');
        });

        Schema::table('class_schedules', function (Blueprint $table) {
            $table->unique(['class_id', 'date'], 'class_schedules_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_classes', function (Blueprint $table) {
            $table->dropUnique('course_classes_unique');
        });

        Schema::table('class_schedules', function (Blueprint $table) {
            $table->dropUnique('class_schedules_unique');
        });
    }
};
