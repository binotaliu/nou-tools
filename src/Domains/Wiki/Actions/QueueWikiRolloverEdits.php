<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Actions;

use App\Jobs\GenerateWikiEditDraft;
use App\Models\WikiEditRequest;
use App\Models\WikiSourceDocument;
use DomainException;
use Illuminate\Support\Str;
use NouTools\Domains\Wiki\Support\WikiRolloverPage;
use NouTools\Domains\Wiki\Support\WikiRolloverPlan;

/**
 * Creates one edit request per page the new document makes stale and queues
 * the editor on each. They belong to no visitor (so skip the visitor draft
 * limits, but each is a model call), are marked with the new document and
 * always wait for an admin (ResolveWikiPublishDecision). A page that already
 * has an open request from this rollover is left alone, so running it twice
 * adds nothing.
 */
final readonly class QueueWikiRolloverEdits
{
    private const int MAX_LISTED_SECTIONS = 12;

    public function __construct(private FindWikiRolloverPlan $findWikiRolloverPlan) {}

    /**
     * @return array{queued: int, skipped: int}
     *
     * @throws DomainException when there is no earlier document to compare with
     */
    public function __invoke(WikiSourceDocument $current): array
    {
        $plan = ($this->findWikiRolloverPlan)($current);

        if ($plan === null) {
            throw new DomainException('這份文件還沒有可以比較的上一版（需要同系列、較早且已擷取完成的文件）。');
        }

        $queued = 0;
        $skipped = 0;

        foreach ($plan->affected() as $rolloverPage) {
            $alreadyOpen = WikiEditRequest::query()
                ->where('wiki_source_document_id', $current->id)
                ->where('wiki_page_id', $rolloverPage->page->id)
                ->open()
                ->exists();

            if ($alreadyOpen) {
                $skipped++;

                continue;
            }

            $editRequest = new WikiEditRequest;
            $editRequest->uuid = (string) Str::uuid();
            $editRequest->wiki_page_id = $rolloverPage->page->id;
            $editRequest->base_revision_id = $rolloverPage->page->current_revision_id;
            $editRequest->wiki_source_document_id = $current->id;
            $editRequest->instruction = $this->instruction($plan, $rolloverPage);
            $editRequest->source_urls = [$current->url];
            $editRequest->saveOrFail();

            GenerateWikiEditDraft::dispatch($editRequest);
            $queued++;
        }

        return ['queued' => $queued, 'skipped' => $skipped];
    }

    private function instruction(WikiRolloverPlan $plan, WikiRolloverPage $rolloverPage): string
    {
        $previous = $plan->previous;
        $current = $plan->current;
        $series = $current->series;

        $lines = [
            "換學期更新：這個條目引用了「{$previous->title}」（".($previous->term ?? '舊版').'），新的「'.$current->title.'」（'.($current->term ?? '新版').'）已儲存。',
            $previous->term !== null && $current->term !== null
                ? "請用 read_source_section 讀 series {$series} 的新舊兩個學期（term {$current->term} 與 {$previous->term}）下列段落，只修改兩者不同的事實，並把引用改成新文件的網址與頁碼。"
                : "請用 read_source_section 讀 series {$series} 新版的下列段落，對照條目現有內容，只修改不同的事實，並把引用改成新文件的網址與頁碼。",
            '沒有變動的內容不要動；新文件已刪除的規定，請在 risk_reasons 說明。',
            '',
            $rolloverPage->pageKnown
                ? '需要比對的段落（舊 → 新）：'
                : '條目的引用沒有指定頁碼，以下是整份文件中有變動的段落，請只比對與條目內容有關的部分：',
        ];

        $sections = $rolloverPage->changedSections();

        foreach (array_slice($sections, 0, self::MAX_LISTED_SECTIONS) as $section) {
            $lines[] = "- {$section['key']} {$section['title']} → ".($section['newKey'] ?? '新文件找不到對應段落');
        }

        if (count($sections) > self::MAX_LISTED_SECTIONS) {
            $lines[] = '…另有 '.(count($sections) - self::MAX_LISTED_SECTIONS).' 段，請用 list_source_sections 查看。';
        }

        return implode("\n", $lines);
    }
}
