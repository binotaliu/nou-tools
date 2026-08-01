<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('student_schedule_items', function (Blueprint $table) {
            $table->foreignId('course_id')->nullable()->after('student_schedule_id')->constrained('courses')->cascadeOnDelete();
        });

        DB::table('student_schedule_items')->update([
            'course_id' => DB::raw('(select course_id from course_classes where course_classes.id = student_schedule_items.course_class_id)'),
        ]);

        Schema::table('student_schedule_items', function (Blueprint $table) {
            $table->dropUnique('student_schedule_items_ssid_ccid_unique');
            $table->foreignId('course_id')->nullable(false)->change();
            $table->foreignId('course_class_id')->nullable()->change();
            $table->unique(['student_schedule_id', 'course_id'], 'student_schedule_items_ssid_cid_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Fails if any row is currently pending (course_class_id null) — a pending
     * row cannot be restored to a NOT NULL course_class_id.
     */
    public function down(): void
    {
        Schema::table('student_schedule_items', function (Blueprint $table) {
            $table->dropUnique('student_schedule_items_ssid_cid_unique');
            $table->foreignId('course_class_id')->nullable(false)->change();
            $table->unique(['student_schedule_id', 'course_class_id'], 'student_schedule_items_ssid_ccid_unique');
        });

        Schema::table('student_schedule_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('course_id');
        });
    }
};
