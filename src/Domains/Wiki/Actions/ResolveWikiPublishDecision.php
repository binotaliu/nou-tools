<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Actions;

use App\Enums\WikiEditRisk;
use App\Models\WikiEditRequest;
use App\Models\WikiRevision;
use NouTools\Domains\Wiki\Support\WikiPublishDecision;
use NouTools\Domains\Wiki\Support\WikiTextDiff;

/**
 * Decides whether a confirmed draft goes live on its own or waits for an
 * admin. It publishes only when every rule holds; each failed rule is kept
 * as a reason so the review queue shows why it is there. Links outside the
 * allowed sources never get this far (ValidateWikiDraft rejects them).
 */
final readonly class ResolveWikiPublishDecision
{
    public function __invoke(WikiEditRequest $editRequest): WikiPublishDecision
    {
        $reasons = [];
        $page = $editRequest->page;
        $base = $editRequest->baseRevision;

        if (! config('wiki.auto_publish.enabled')) {
            $reasons[] = '自動發布已關閉';
        }

        if ($page === null || $base === null) {
            return new WikiPublishDecision([...$reasons, '新條目']);
        }

        if ($page->is_protected) {
            $reasons[] = '受保護的條目';
        }

        if ($editRequest->risk() !== WikiEditRisk::Low) {
            $reasons[] = 'AI 評估風險為'.($editRequest->risk() ?? WikiEditRisk::High)->label();
        }

        if ($editRequest->draft_title !== $base->title) {
            $reasons[] = '變更標題';
        }

        if ($this->sorted($editRequest->draft_categories ?? []) !== $this->sorted($base->categories)) {
            $reasons[] = '變更分類';
        }

        $diff = WikiTextDiff::compare($base->body, (string) $editRequest->draft_body);

        if ($diff->changedRatio() > (float) config('wiki.auto_publish.max_changed_ratio')) {
            $reasons[] = sprintf('修改幅度 %d%%', (int) round($diff->changedRatio() * 100));
        }

        if ($diff->removedChars() > (int) config('wiki.auto_publish.max_removed_chars')) {
            $reasons[] = "刪除 {$diff->removedChars()} 字";
        }

        $recentEditByOthers = WikiRevision::query()
            ->where('wiki_page_id', $page->id)
            ->where('created_at', '>=', now()->subMinutes((int) config('wiki.auto_publish.cooldown_minutes')))
            ->where(fn ($query) => $query
                ->whereNull('student_schedule_id')
                ->orWhere('student_schedule_id', '!=', $editRequest->student_schedule_id))
            ->where('origin', '!=', 'import')
            ->exists();

        if ($recentEditByOthers) {
            $reasons[] = '其他人剛編輯過此條目';
        }

        return new WikiPublishDecision($reasons);
    }

    /**
     * @param  list<string>  $values
     * @return list<string>
     */
    private function sorted(array $values): array
    {
        sort($values);

        return $values;
    }
}
