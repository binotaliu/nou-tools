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
        Schema::table('courses', function (Blueprint $table) {
            $table->string('description_url')->nullable()->after('term');
            $table->string('credit_type')->nullable()->after('description_url');
            $table->integer('credits')->nullable()->after('credit_type');
            $table->string('department')->nullable()->after('credits');
            $table->string('in_person_class_type')->nullable()->after('department');
            $table->string('media')->nullable()->after('in_person_class_type');
            $table->string('multimedia_url')->nullable()->after('media');
            $table->string('nature')->nullable()->after('multimedia_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn([
                'description_url',
                'credit_type',
                'credits',
                'department',
                'in_person_class_type',
                'media',
                'multimedia_url',
                'nature',
            ]);
        });
    }
};
