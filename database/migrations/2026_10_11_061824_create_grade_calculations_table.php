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
        Schema::create('grade_calculations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_schedule_id')->constrained()->cascadeOnDelete();
            $table->string('term');
            $table->json('scores');
            $table->json('weights');
            $table->timestamps();

            // 每個課表中每個學期只能有一份成績計算機資料
            $table->unique(['student_schedule_id', 'term']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grade_calculations');
    }
};
