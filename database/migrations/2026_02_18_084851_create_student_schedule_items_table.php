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
        Schema::create('student_schedule_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_schedule_id')->constrained('student_schedules')->cascadeOnDelete();
            $table->foreignId('course_class_id')->constrained('course_classes')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(
                ['student_schedule_id', 'course_class_id'],
                'student_schedule_items_ssid_ccid_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_schedule_items');
    }
};
