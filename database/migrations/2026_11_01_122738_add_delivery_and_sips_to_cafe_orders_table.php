<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cafe_orders', function (Blueprint $table) {
            // The drink arrives a few seconds after ordering.
            $table->timestamp('ready_at')->nullable()->after('to_go');
            $table->unsignedTinyInteger('sips_taken')->default(0)->after('ready_at');
        });
    }

    public function down(): void
    {
        Schema::table('cafe_orders', function (Blueprint $table) {
            $table->dropColumn(['ready_at', 'sips_taken']);
        });
    }
};
