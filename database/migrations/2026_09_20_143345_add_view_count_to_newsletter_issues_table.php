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
        Schema::table('newsletter_issues', function (Blueprint $table) {
            $table->unsignedInteger('view_count')->default(0)->after('ai_drafted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('newsletter_issues', function (Blueprint $table) {
            $table->dropColumn('view_count');
        });
    }
};
