<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Moves the only page of the old file-based 知識庫 (resources/articles/kb/
 * about-nou.md) into the wiki, so /kb/about-nou keeps leading somewhere. The
 * text is copied here because the file is gone.
 */
return new class extends Migration
{
    private const string SLUG = 'about-nou';

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
            'is_protected' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $revisionId = DB::table('wiki_revisions')->insertGetId([
            'wiki_page_id' => $pageId,
            'title' => '關於國立空中大學',
            'description' => '認識國立空中大學的歷史、特色與學習方式。',
            'body' => $this->body(),
            'categories' => json_encode(['認識空大'], JSON_UNESCAPED_UNICODE),
            'edit_summary' => '從舊知識庫匯入',
            'origin' => 'import',
            'created_at' => $now,
        ]);

        DB::table('wiki_pages')->where('id', $pageId)->update(['current_revision_id' => $revisionId]);

        $categoryId = DB::table('wiki_categories')->where('name', '認識空大')->value('id')
            ?? DB::table('wiki_categories')->insertGetId(['name' => '認識空大', 'created_at' => $now, 'updated_at' => $now]);

        DB::table('wiki_category_page')->insert(['wiki_category_id' => $categoryId, 'wiki_page_id' => $pageId]);
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
        DB::table('wiki_categories')->whereNotExists(fn ($query) => $query
            ->from('wiki_category_page')
            ->whereColumn('wiki_category_page.wiki_category_id', 'wiki_categories.id'))
            ->delete();
    }

    private function body(): string
    {
        return <<<'MD'
            國立空中大學（National Open University，簡稱 NOU）是台灣第一所專門提供遠距教育的公立大學。

            ## 學校特色

            ### 彈性學習

            空大提供高度彈性的學習方式：

            - **不限修業年限**：可依個人步調安排學習進度
            - **多元學習管道**：課本自學、網路教學、廣播電視
            - **面授輔導**：提供實體與線上面授課程

            ### 學習方式

            空大的課程主要包含以下幾種學習方式：

            1. **課本閱讀**：每門課程都有精心編撰的教科書
            2. **網路教學影片**：透過線上平台觀看教學影音
            3. **面授輔導**：定期舉辦實體或視訊面授課程
            4. **作業與考試**：完成平時作業與期中期末考試

            ## 課程類型

            ### 視訊面授

            透過視訊會議系統進行的即時互動課程，讓你可以在家就能參與面授。

            ### 實體面授

            在學習指導中心舉辦的實體課程，適合需要實作或討論的科目。

            ## 學習資源

            空大提供豐富的學習資源：

            - 線上學習平台
            - 數位圖書館
            - 學習輔導服務
            - 校友互助社群

            透過這些資源，即使是遠距學習，也能獲得完整的學習支援。

            MD;
    }
};
