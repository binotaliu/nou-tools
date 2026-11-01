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
        // Like study_room_seats, a row is both "this seat exists" and "who is
        // sitting in it", so claiming one is a single conditional UPDATE.
        Schema::create('cafe_seats', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 16);
            $table->string('group_code', 8)->nullable();
            $table->unsignedSmallInteger('seat_number');
            $table->string('code', 16)->unique();
            $table->string('label');
            $table->foreignId('student_schedule_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->timestamp('occupied_at')->nullable();
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cafe_seats');
    }
};
