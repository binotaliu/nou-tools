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
        Schema::table('courses', function (Blueprint $table) {
            $table->date('midterm_date')->nullable();
            $table->date('final_date')->nullable();
            $table->string('exam_time_start')->nullable();
            $table->string('exam_time_end')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['midterm_date', 'final_date', 'exam_time_start', 'exam_time_end']);
        });
    }
};
