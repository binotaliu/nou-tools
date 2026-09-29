<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_calendar_events', function (Blueprint $table): void {
            $table->id();
            $table->string('term', 5);
            $table->date('start_date');
            $table->date('end_date');
            $table->string('name');
            $table->boolean('is_countdown')->default(false);
            $table->boolean('is_important')->default(true);
            $table->timestamps();

            $table->index(['term', 'start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_calendar_events');
    }
};
