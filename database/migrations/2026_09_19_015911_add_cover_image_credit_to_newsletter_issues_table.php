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
            $table->string('cover_image_credit_name')->nullable()->after('cover_image');
            $table->string('cover_image_credit_url')->nullable()->after('cover_image_credit_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('newsletter_issues', function (Blueprint $table) {
            $table->dropColumn(['cover_image_credit_name', 'cover_image_credit_url']);
        });
    }
};
