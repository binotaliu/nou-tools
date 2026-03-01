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
        Schema::create('previous_exams', function (Blueprint $table) {
            $table->id();
            $table->string('course_name')->index();
            $table->string('course_no')->index();
            $table->string('term')->index();
            $table->string('midterm_reference_primary')->nullable();
            $table->string('midterm_reference_secondary')->nullable();
            $table->string('final_reference_primary')->nullable();
            $table->string('final_reference_secondary')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('previous_exams');
    }
};
