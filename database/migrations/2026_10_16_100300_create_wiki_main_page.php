<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The intro of the wiki's homepage is the protected `main-page` page, edited
 * by admins in Filament like any other page. This starts it off.
 */
return new class extends Migration
{
    private const string SLUG = 'main-page';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::table('wiki_pages')->where('slug', self::SLUG)->exists()) {
            return;
        }

        $now = now();

        $pageId = DB::table('wiki_pages')->insertGetId([
            'slug' => self::SLUG,
            'is_protected' => true,
            'is_featured' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $revisionId = DB::table('wiki_revisions')->insertGetId([
            'wiki_page_id' => $pageId,
            'title' => '首頁',
            'description' => '知識庫首頁的介紹。',
            'body' => $this->body(),
            'categories' => '[]',
            'edit_summary' => '建立首頁',
            'origin' => 'import',
            'created_at' => $now,
        ]);

        DB::table('wiki_pages')->where('id', $pageId)->update(['current_revision_id' => $revisionId]);

        DB::table('wiki_links')->insert(['from_page_id' => $pageId, 'to_slug' => 'about-nou']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $pageId = DB::table('wiki_pages')->where('slug', self::SLUG)->value('id');

        if ($pageId === null) {
            return;
        }

        DB::table('wiki_pages')->where('id', $pageId)->update(['current_revision_id' => null]);
        DB::table('wiki_pages')->where('id', $pageId)->delete();
    }

    private function body(): string
    {
        return <<<'MD'
            歡迎來到空大知識庫！這裡整理空大同學常遇到的問題：選課、考試、學分抵免、校務流程……第一次來的話，可以從下面幾個地方開始。

            :::cards
            - [[about-nou|認識空大]] — 學校的歷史、特色與學習方式
            - [學校行事曆](/school-calendar) — 選課、考試與重要日期
            - [本學期開課表](/courses/schedule) — 這學期開了哪些課
            - [校園通訊錄](/directory) — 各單位與學習指導中心的聯絡方式
            :::

            找不到答案？用上面的搜尋找找看，或是建立新條目，請 AI 編輯幫你寫。
            MD;
    }
};
