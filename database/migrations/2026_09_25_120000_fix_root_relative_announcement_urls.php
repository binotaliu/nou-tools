<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The HTML scrapers used to append a root-relative href
     * (`/upload/cont_att/x.pdf`) to the source's whole base URL, giving
     * `https://www2.nou.edu.tw/coach/upload/cont_att/x.pdf`, a 404. The link
     * belongs on the host root. Rows still in a source's current listing heal
     * on the next fetch; this repairs the ones that have dropped off it.
     */
    public function up(): void
    {
        DB::table('announcements')
            ->where('source_id', 'like', '/%')
            ->orderBy('id')
            ->each(function (object $announcement): void {
                $origin = parse_url($announcement->url, PHP_URL_SCHEME).'://'.parse_url($announcement->url, PHP_URL_HOST);
                $port = parse_url($announcement->url, PHP_URL_PORT);
                $fixed = $origin.($port ? ':'.$port : '').$announcement->source_id;

                if ($fixed !== $announcement->url && str_ends_with($announcement->url, $announcement->source_id)) {
                    DB::table('announcements')->where('id', $announcement->id)->update(['url' => $fixed]);
                }
            });
    }

    /**
     * The old URLs were wrong, so there is nothing worth restoring.
     */
    public function down(): void {}
};
