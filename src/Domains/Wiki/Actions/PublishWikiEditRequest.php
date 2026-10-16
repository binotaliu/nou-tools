<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Actions;

use App\Enums\WikiEditRequestStatus;
use App\Enums\WikiRevisionOrigin;
use App\Models\User;
use App\Models\WikiEditRequest;
use App\Models\WikiPage;
use NouTools\Domains\Wiki\DataTransferObjects\PublishWikiRevisionDTO;

/**
 * Turns a request's draft into the page's new revision. Callers hold the
 * transaction and have checked the base revision is still current.
 */
final readonly class PublishWikiEditRequest
{
    public function __construct(private PublishWikiRevision $publishWikiRevision) {}

    public function __invoke(WikiEditRequest $editRequest, ?User $reviewer = null, ?string $slug = null): WikiEditRequest
    {
        $revision = ($this->publishWikiRevision)(
            $editRequest->page,
            new PublishWikiRevisionDTO(
                title: (string) $editRequest->draft_title,
                description: $editRequest->draft_description,
                body: (string) $editRequest->draft_body,
                categories: $editRequest->draft_categories ?? [],
                editSummary: $editRequest->draft_edit_summary,
                origin: WikiRevisionOrigin::AiEdit,
                editRequestId: $editRequest->id,
                userId: $reviewer?->id,
                studentScheduleId: $editRequest->student_schedule_id,
            ),
            $editRequest->isNewPage() ? ($slug ?? $editRequest->draft_slug) : null,
        );

        $editRequest->wiki_page_id ??= $revision->wiki_page_id;
        $editRequest->published_revision_id = $revision->id;
        $editRequest->status = WikiEditRequestStatus::Published;

        if ($reviewer !== null) {
            $editRequest->reviewed_by = $reviewer->id;
            $editRequest->reviewed_at = now();
        }

        $editRequest->saveOrFail();
        $editRequest->setRelation('page', WikiPage::query()->find($revision->wiki_page_id));

        return $editRequest;
    }
}
