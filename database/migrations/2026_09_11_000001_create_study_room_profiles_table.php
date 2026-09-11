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
        Schema::create('study_room_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_schedule_id')->constrained()->cascadeOnDelete()->unique();
            $table->string('nickname')->nullable();
            $table->string('emoji', 16)->nullable();
            $table->timestamp('nickname_changed_at')->nullable();
            $table->timestamp('nickname_reset_at')->nullable();
            $table->foreignId('nickname_reset_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('study_room_profiles');
    }
};
