<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Actions;

use App\Models\WikiPage;
use App\Models\WikiRevision;
use NouTools\Domains\Wiki\PageData\WikiHistoryPageData;
use NouTools\Domains\Wiki\ViewModels\WikiPageLinkViewModel;
use NouTools\Domains\Wiki\ViewModels\WikiRevisionSummaryViewModel;

final readonly class ShowWikiHistoryPage
{
    public function __invoke(WikiPage $page): WikiHistoryPageData
    {
        $page->loadMissing('currentRevision');

        return new WikiHistoryPageData(
            page: WikiPageLinkViewModel::fromModel($page),
            revisions: $page->revisions()
                ->with('studentSchedule.studyRoomProfile:id,student_schedule_id,nickname')
                ->get(['id', 'wiki_page_id', 'student_schedule_id', 'title', 'edit_summary', 'origin', 'created_at'])
                ->map(fn (WikiRevision $revision): WikiRevisionSummaryViewModel => WikiRevisionSummaryViewModel::fromModel($revision, $page->slug, $page->current_revision_id))
                ->all(),
        );
    }
}
