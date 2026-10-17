<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Actions;

use App\Jobs\GenerateWikiEditDraft;
use App\Models\WikiEditRequest;
use Illuminate\Support\Str;
use NouTools\Domains\Schedules\ValueObjects\StudentScheduleCookie;
use NouTools\Domains\Wiki\DataTransferObjects\CreateWikiEditRequestDTO;
use NouTools\Domains\Wiki\Exceptions\WikiDraftLimitReachedException;
use NouTools\Domains\Wiki\Exceptions\WikiEditingNotAllowedException;

/**
 * Records a visitor's request and has the AI editor draft it after the
 * response; the status page polls until the draft is there.
 */
final readonly class CreateWikiEditRequest
{
    public function __construct(
        private FindWikiPageBySlug $findWikiPageBySlug,
        private EnsureWikiEditingAllowed $ensureWikiEditingAllowed,
        private ConsumeWikiDraftAllowance $consumeWikiDraftAllowance,
        private StoreWikiScreenshots $storeWikiScreenshots,
    ) {}

    /**
     * @throws WikiEditingNotAllowedException
     * @throws WikiDraftLimitReachedException
     */
    public function __invoke(CreateWikiEditRequestDTO $data, ?StudentScheduleCookie $viewer, ?string $ip): WikiEditRequest
    {
        $page = null;

        if (filled($data->pageSlug)) {
            $page = ($this->findWikiPageBySlug)((string) $data->pageSlug)
                ?? throw new WikiEditingNotAllowedException('找不到要修改的條目。');
        }

        $schedule = ($this->ensureWikiEditingAllowed)($viewer, $page);
        $ipHash = WikiEditRequest::hashIp($ip);

        ($this->consumeWikiDraftAllowance)($schedule, $ipHash);

        $editRequest = new WikiEditRequest;
        $editRequest->fillFromDTO($data);
        $editRequest->screenshots = ($this->storeWikiScreenshots)($data->screenshots);
        $editRequest->uuid = (string) Str::uuid();
        $editRequest->wiki_page_id = $page?->id;
        $editRequest->base_revision_id = $page?->current_revision_id;
        $editRequest->student_schedule_id = $schedule->id;
        $editRequest->ip_hash = $ipHash;

        if ($page !== null) {
            $editRequest->requested_slug = null;
            $editRequest->requested_title = null;
        }

        $editRequest->saveOrFail();

        GenerateWikiEditDraft::dispatchAfterResponse($editRequest);

        return $editRequest;
    }
}
