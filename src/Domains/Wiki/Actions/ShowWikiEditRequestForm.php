<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Actions;

use App\Models\StudentSchedule;
use App\Models\WikiPage;
use NouTools\Domains\Schedules\ValueObjects\StudentScheduleCookie;
use NouTools\Domains\Wiki\PageData\WikiEditRequestFormPageData;
use NouTools\Domains\Wiki\Support\WikiSourceDomains;
use NouTools\Domains\Wiki\ViewModels\WikiPageLinkViewModel;

final readonly class ShowWikiEditRequestForm
{
    public function __invoke(?WikiPage $page, ?StudentScheduleCookie $viewer, ?string $requestedSlug = null, ?string $requestedTitle = null): WikiEditRequestFormPageData
    {
        $page?->loadMissing('currentRevision');
        $schedule = $viewer === null ? null : StudentSchedule::query()->find($viewer->id);

        $requestedSlug = $requestedSlug !== null && WikiPage::isValidSlug($requestedSlug) ? $requestedSlug : null;

        return new WikiEditRequestFormPageData(
            page: $page !== null ? WikiPageLinkViewModel::fromModel($page) : null,
            requestedSlug: $page === null ? $requestedSlug : null,
            requestedTitle: $page === null && filled($requestedTitle) ? mb_substr((string) $requestedTitle, 0, (int) config('wiki.max_title_length')) : null,
            signedIn: $schedule !== null,
            blocked: $schedule?->wiki_blocked_at !== null,
            isProtected: (bool) $page?->is_protected,
            turnstileSiteKey: config('turnstile.turnstile_site_key'),
            instructionMaxLength: (int) config('wiki.instruction_max_length'),
            maxSourceUrls: (int) config('wiki.max_source_urls'),
            sourceDomains: WikiSourceDomains::all(),
            writingGuideUrl: route('wiki.guide', 'writing'),
            syntaxGuideUrl: route('wiki.guide', 'syntax'),
        );
    }
}
