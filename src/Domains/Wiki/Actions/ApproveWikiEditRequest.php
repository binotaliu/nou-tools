<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Actions;

use App\Enums\WikiEditRequestStatus;
use App\Models\User;
use App\Models\WikiEditRequest;
use App\Models\WikiPage;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * An admin publishes a request waiting for review, optionally under a
 * different slug when it creates a page.
 */
final readonly class ApproveWikiEditRequest
{
    public function __construct(private PublishWikiEditRequest $publishWikiEditRequest) {}

    /**
     * @throws DomainException when the request isn't pending or the page moved on
     */
    public function __invoke(WikiEditRequest $editRequest, User $reviewer, ?string $slug = null): WikiEditRequest
    {
        return DB::transaction(function () use ($editRequest, $reviewer, $slug): WikiEditRequest {
            $editRequest = WikiEditRequest::query()->lockForUpdate()->findOrFail($editRequest->id);

            if ($editRequest->status !== WikiEditRequestStatus::PendingReview) {
                throw new DomainException('這個修改不在待審核狀態。');
            }

            if ($editRequest->wiki_page_id !== null) {
                $page = WikiPage::query()->lockForUpdate()->findOrFail($editRequest->wiki_page_id);

                if ($page->current_revision_id !== $editRequest->base_revision_id) {
                    throw new DomainException('條目在這份草稿之後已經被修改過，請退回並請提出者依最新版本重新產生。');
                }
            }

            return ($this->publishWikiEditRequest)($editRequest, $reviewer, filled($slug) ? $slug : null);
        });
    }
}
