<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('announcements')) {
            return;
        }

        if (Schema::hasColumn('announcements', 'announcement_source_id')) {
            Schema::table('announcements', function (Blueprint $table) {
                $table->string('source_key')->nullable()->after('id');
                $table->string('source_name')->nullable()->after('source_key');
                $table->string('category')->nullable()->after('source_name');
            });

            $sources = Schema::hasTable('announcement_sources')
                ? DB::table('announcement_sources')->get()->keyBy('id')
                : collect();

            DB::table('announcements')
                ->select(['id', 'announcement_source_id'])
                ->orderBy('id')
                ->get()
                ->each(function (object $announcement) use ($sources): void {
                    $source = $sources->get($announcement->announcement_source_id);

                    if ($source === null) {
                        return;
                    }

                    $sourceKey = str($source->name)
                        ->append('-', $source->category)
                        ->slug()
                        ->value();

                    DB::table('announcements')
                        ->where('id', $announcement->id)
                        ->update([
                            'source_key' => $sourceKey,
                            'source_name' => $source->name,
                            'category' => $source->category,
                        ]);
                });

            Schema::table('announcements', function (Blueprint $table) {
                $table->dropUnique(['announcement_source_id', 'source_id']);
            });

            Schema::table('announcements', function (Blueprint $table) {
                $table->dropConstrainedForeignId('announcement_source_id');
            });

            Schema::table('announcements', function (Blueprint $table) {
                $table->unique(['source_key', 'source_id']);
            });
        }

        if (Schema::hasTable('announcement_sources')) {
            Schema::drop('announcement_sources');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('announcements') || Schema::hasColumn('announcements', 'announcement_source_id')) {
            return;
        }

        Schema::create('announcement_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category');
            $table->text('fetch_url')->nullable();
            $table->string('fetcher_type')->nullable();
            $table->json('fetcher_config')->nullable();
            $table->boolean('tracks_expiry')->default(false);
            $table->boolean('is_active')->default(true);
            $table->dateTime('last_fetched_at')->nullable();
            $table->timestamps();
        });

        $sourceIdMap = [];

        DB::table('announcements')
            ->select(['source_key', 'source_name', 'category'])
            ->distinct()
            ->orderBy('source_name')
            ->get()
            ->each(function (object $source) use (&$sourceIdMap): void {
                $sourceId = DB::table('announcement_sources')->insertGetId([
                    'name' => $source->source_name,
                    'category' => $source->category,
                    'fetch_url' => '',
                    'fetcher_type' => '',
                    'fetcher_config' => null,
                    'tracks_expiry' => false,
                    'is_active' => true,
                    'last_fetched_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $sourceIdMap[$source->source_key] = $sourceId;
            });

        Schema::table('announcements', function (Blueprint $table) {
            $table->unsignedBigInteger('announcement_source_id')->nullable()->after('id');
        });

        DB::table('announcements')
            ->select(['id', 'source_key'])
            ->orderBy('id')
            ->get()
            ->each(function (object $announcement) use ($sourceIdMap): void {
                DB::table('announcements')
                    ->where('id', $announcement->id)
                    ->update([
                        'announcement_source_id' => $sourceIdMap[$announcement->source_key] ?? null,
                    ]);
            });

        Schema::table('announcements', function (Blueprint $table) {
            $table->dropUnique(['source_key', 'source_id']);
        });

        Schema::table('announcements', function (Blueprint $table) {
            $table->dropColumn(['source_key', 'source_name', 'category']);
        });

        Schema::table('announcements', function (Blueprint $table) {
            $table->foreign('announcement_source_id')
                ->references('id')
                ->on('announcement_sources')
                ->cascadeOnDelete();

            $table->unique(['announcement_source_id', 'source_id']);
        });
    }
};
