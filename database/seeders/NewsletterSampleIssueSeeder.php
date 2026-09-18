<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\NewsletterSection;
use App\Models\NewsletterColumn;
use App\Models\NewsletterItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use NouTools\Domains\Newsletter\Actions\CreateNewsletterDraft;
use NouTools\Domains\Newsletter\Actions\PublishNewsletterIssue;
use NouTools\Domains\Newsletter\Actions\ResolveNewsletterIssueSchedule;

/**
 * Local-only placeholder for the first issue (2026-W39), filled with every
 * Markdown construct the article pipeline supports so the newsletter
 * frontend can be checked by eye. Not called from DatabaseSeeder; run with
 * `php artisan db:seed --class=NewsletterSampleIssueSeeder`. Re-running
 * replaces the issue's items and columns.
 */
final class NewsletterSampleIssueSeeder extends Seeder
{
    public function run(
        ResolveNewsletterIssueSchedule $resolveNewsletterIssueSchedule,
        CreateNewsletterDraft $createNewsletterDraft,
        PublishNewsletterIssue $publishNewsletterIssue,
    ): void {
        $issue = $createNewsletterDraft($resolveNewsletterIssueSchedule->forIssueKey('2026-W39'));

        DB::transaction(function () use ($issue): void {
            $issue->items()->delete();
            $issue->columns()->delete();

            $issue->title = '浣熊的空大雙週報 創刊號';
            // The real calendar has nothing in this window, so placeholder
            // events stand in to exercise the event list.
            $issue->highlights_events = [
                ['start' => '2026-09-21', 'end' => '2026-09-30', 'name' => '【範例】115上學期加退選'],
                ['start' => '2026-09-25', 'end' => '2026-09-25', 'name' => '【範例】期中考報名截止'],
                ['start' => '2026-10-03', 'end' => '2026-10-04', 'name' => '【範例】第一次面授'],
            ];
            $issue->highlights_intro = <<<'MD'
                歡迎來到**創刊號**！接下來兩週最要緊的是 ==加退選== 與 *期中考報名*，別忘了在期限內完成。
                另外，~~原訂的面授調整公告~~ 已經撤回，請以最新公告為準。詳情可參考 [教務處網站](https://studadm.nou.edu.tw/)。
                MD;
            $issue->saveOrFail();

            foreach ($this->newsItems() as $position => $item) {
                $this->createItem($issue->id, NewsletterSection::News, $position, $item);
            }

            foreach ($this->centerItems() as $position => $item) {
                $this->createItem($issue->id, NewsletterSection::Centers, $position, $item);
            }

            foreach ($this->columns() as $position => $column) {
                $newsletterColumn = new NewsletterColumn;
                $newsletterColumn->fill([...$column, 'newsletter_issue_id' => $issue->id, 'position' => $position]);
                $newsletterColumn->saveOrFail();
            }
        });

        $publishNewsletterIssue($issue->refresh());
    }

    /**
     * @param  array{source_name: string, headline: string, summary: string, url: ?string}  $item
     */
    private function createItem(int $issueId, NewsletterSection $section, int $position, array $item): void
    {
        $newsletterItem = new NewsletterItem;
        $newsletterItem->fill([
            ...$item,
            'newsletter_issue_id' => $issueId,
            'section' => $section,
            'position' => $position,
        ]);
        $newsletterItem->saveOrFail();
    }

    /**
     * @return array<int, array{source_name: string, headline: string, summary: string, url: ?string}>
     */
    private function newsItems(): array
    {
        return [
            [
                'source_name' => '教務處',
                'headline' => '【範例】115 上學期加退選開始，9 月 30 日截止',
                'summary' => '已選課的同學可在期限內**加選或退選**，退選的學分費依規定比例退還。',
                'url' => 'https://studadm.nou.edu.tw/',
            ],
            [
                'source_name' => '教務處',
                'headline' => '【範例】期中考報名與考場選擇',
                'summary' => <<<'MD'
                    報名步驟：

                    1. 登入教務系統
                    2. 選擇考區與考場
                    3. 確認後送出

                    > [!TIP] 小撇步
                    > 想和同學同考場，記得*同一天*報名。
                    MD,
                'url' => 'https://studadm.nou.edu.tw/',
            ],
            [
                'source_name' => '學務處',
                'headline' => '【範例】獎助學金申請開跑',
                'summary' => '包含清寒、急難與校友會獎學金，詳細資格請見原文。申請表可下載 `申請表.docx`。',
                'url' => 'https://www.nou.edu.tw/',
            ],
            [
                'source_name' => '管理與資訊學系',
                'headline' => '【範例】學系座談會：選課與學習規劃（沒有原文連結的消息）',
                'summary' => '',
                'url' => null,
            ],
        ];
    }

    /**
     * @return array<int, array{source_name: string, headline: string, summary: string, url: ?string}>
     */
    private function centerItems(): array
    {
        return [
            [
                'source_name' => '臺北中心',
                'headline' => '【範例】讀書會招募：一起準備期中考',
                'summary' => '每週六下午，地點在中心 3 樓教室，名額 **20 人**。',
                'url' => 'https://www.nou.edu.tw/',
            ],
            [
                'source_name' => '臺北中心',
                'headline' => '【範例】中心服務時間調整',
                'summary' => '國慶連假期間暫停服務。',
                'url' => 'https://www.nou.edu.tw/',
            ],
            [
                'source_name' => '臺中中心',
                'headline' => '【範例】秋季健行活動報名',
                'summary' => '10 月中旬舉辦，歡迎攜家帶眷。',
                'url' => 'https://www.nou.edu.tw/',
            ],
            [
                'source_name' => '高雄中心',
                'headline' => '【範例】數位學習平台操作說明會',
                'summary' => '新生必看！說明會提供[線上報名](https://www.nou.edu.tw/)。',
                'url' => 'https://www.nou.edu.tw/',
            ],
        ];
    }

    /**
     * @return array<int, array{title: string, author: ?string, body: string}>
     */
    private function columns(): array
    {
        return [
            [
                'title' => '浣熊站長的自言自語',
                'author' => '浣熊站長',
                'body' => <<<'MD'
                    這一欄示範各種 Markdown 語法，正式發刊前請換成真正的內容。

                    ## 二級標題

                    ### 三級標題

                    一般段落，包含**粗體**、*斜體*、~~刪除線~~、==螢光標記==、`行內程式碼`，以及[一般連結](https://www.nou.edu.tw/)和自動連結 https://www.nou.edu.tw/ 。

                    - 無序清單
                    - 第二項
                      - 巢狀項目
                      - 另一個巢狀項目

                    1. 有序清單
                    2. 第二項

                    - [x] GFM 已完成的待辦
                    - [ ] GFM 未完成的待辦

                    > 一般的引用區塊。
                    >
                    > 可以有多段。

                    | 科目 | 學分 | 備註 |
                    | --- | :---: | ---: |
                    | 計算機概論 | 3 | 必修 |
                    | 心理學 | 2 | 選修 |

                    ```php
                    echo '程式碼區塊';
                    ```

                    ---

                    > [!NOTE]
                    > GitHub 風格的補充說明。

                    > [!WARNING] 自訂標題的注意事項
                    > 內容文字。

                    :::important
                    `:::` 容器形式的提示框。
                    :::

                    :::money 學分費
                    每學分 **1,040 元**（範例數字）。
                    :::

                    :::summary
                    這是懶人包：加退選到 9/30，期中考記得報名。
                    :::

                    :::dialogue
                    新生：站長，雙週報是什麼？

                    浣熊站長：每兩週幫大家整理一次學校公告的刊物！

                        縮排的段落會接在同一位說話者後面。

                    新生（開心）：太好了！
                    :::
                    MD,
            ],
            [
                'title' => '互動元件測試',
                'author' => null,
                'body' => <<<'MD'
                    以下是需要 JavaScript 才會動起來的容器。

                    :::checklist
                    - [ ] 確認選課清單
                    - [ ] 報名期中考
                    - [ ] 繳交第一次作業
                    :::

                    :::countdown
                    **加退選截止**: 2026-09-21 ~ 2026-09-30

                    **期中考**: 2026-11-07 ~ 2026-11-08
                    :::

                    ::::tabs
                    :::tab 大學部
                    大學部同學請注意……
                    :::
                    :::tab 碩士班
                    碩士班同學請注意……
                    :::
                    ::::

                    :::steps 報名期中考的三個步驟
                    1. 登入教務系統
                    2. 選擇考場
                    3. 送出並截圖
                    :::

                    :::faq
                    ### 雙週報多久出一期？
                    每兩週，隔週一發刊。

                    ### 可以投稿嗎？
                    未來會開放同學投稿。
                    :::

                    :::timeline
                    - **9/21**：創刊號發刊
                    - **9/30**：加退選截止
                    - **10/5**：第二期發刊
                    :::

                    :::cards
                    - [學校公告](/announcements) — 所有來源的公告列表
                    - [自習室](/study-room) — 一起安靜讀書
                    :::

                    :::cta
                    [看看所有學校公告](/announcements)
                    :::
                    MD,
            ],
        ];
    }
}
