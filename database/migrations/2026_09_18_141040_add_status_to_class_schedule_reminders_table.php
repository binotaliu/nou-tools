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
        Schema::table('class_schedule_reminders', function (Blueprint $table) {
            $table->string('status')->default('failed')->after('student_schedule_id');
            $table->string('failure_reason')->nullable()->after('status');
            $table->timestamp('sent_at')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('class_schedule_reminders', function (Blueprint $table) {
            $table->dropColumn(['status', 'failure_reason']);
            $table->timestamp('sent_at')->nullable(false)->change();
        });
    }
};
