<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The café now seats people in `study_room_seats` (room = cafe, floor 0),
     * so the focus timer and everything built on it works there. Café seats
     * are re-created by `SyncCafeSeats`; anyone sitting at deploy time is
     * simply unseated.
     */
    public function up(): void
    {
        Schema::table('study_room_seats', function (Blueprint $table) {
            $table->string('room', 16)->default('study')->after('id');
            $table->index(['room', 'floor']);
        });

        Schema::dropIfExists('cafe_seats');
    }

    public function down(): void
    {
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

        Schema::table('study_room_seats', function (Blueprint $table) {
            $table->dropIndex(['room', 'floor']);
            $table->dropColumn('room');
        });
    }
};
