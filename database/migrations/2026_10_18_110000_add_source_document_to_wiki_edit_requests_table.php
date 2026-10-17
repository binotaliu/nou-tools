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
            // Set on requests the admin creates when a new semester's document
            // replaces an older one (the 換學期更新): they have no visitor, so
            // no schedule and no ip.
            $table->foreignId('wiki_source_document_id')->nullable()->after('student_schedule_id')
                ->constrained()->nullOnDelete();
            $table->string('ip_hash', 64)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wiki_edit_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('wiki_source_document_id');
        });
    }
};
