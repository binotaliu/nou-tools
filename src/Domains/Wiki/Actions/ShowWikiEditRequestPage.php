<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Actions;

use App\Models\WikiEditRequest;
use NouTools\Domains\Schedules\ValueObjects\StudentScheduleCookie;
use NouTools\Domains\Wiki\PageData\WikiEditRequestPageData;
use NouTools\Domains\Wiki\ViewModels\WikiEditRequestViewModel;

final readonly class ShowWikiEditRequestPage
{
    public function __construct(private RenderWikiMarkdown $renderWikiMarkdown) {}

    public function __invoke(WikiEditRequest $editRequest, ?StudentScheduleCookie $viewer): WikiEditRequestPageData
    {
        $editRequest->loadMissing(['page.currentRevision', 'baseRevision']);

        return new WikiEditRequestPageData(
            request: WikiEditRequestViewModel::fromModel($editRequest, $this->renderWikiMarkdown, $editRequest->belongsToSchedule($viewer)),
            instructionMaxLength: (int) config('wiki.instruction_max_length'),
        );
    }
}
