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
        Schema::table('wiki_edit_requests', function (Blueprint $table) {
            // Screenshots the visitor attached: [{path, name}], files on the
            // wiki_screenshots disk.
            $table->json('screenshots')->nullable()->after('source_urls');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wiki_edit_requests', function (Blueprint $table) {
            $table->dropColumn('screenshots');
        });
    }
};
