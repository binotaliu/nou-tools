<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_days', function (Blueprint $table): void {
            $table->id();
            $table->date('date')->unique();
            $table->boolean('is_red')->default(true);
            $table->string('label', 20)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_days');
    }
};
