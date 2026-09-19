<?php

declare(strict_types=1);

namespace NouTools\Domains\Newsletter\ViewModels;

use App\Models\NewsletterIssue;
use Illuminate\Support\Facades\Storage;
use Spatie\LaravelData\Data;

final class NewsletterIssueSummaryViewModel extends Data
{
    public function __construct(
        public string $issueKey,
        public string $title,
        public string $publishesOn,
        public string $highlightsFrom,
        public string $highlightsTo,
        public string $url,
        public ?string $coverImageUrl,
    ) {}

    public static function fromModel(NewsletterIssue $issue): self
    {
        return new self(
            issueKey: $issue->issue_key,
            title: $issue->displayTitle(),
            publishesOn: $issue->publishes_on->toDateString(),
            highlightsFrom: $issue->highlights_from->toDateString(),
            highlightsTo: $issue->highlights_to->toDateString(),
            url: route('newsletter.show', $issue->issue_key),
            coverImageUrl: $issue->cover_image !== null ? Storage::disk('public')->url($issue->cover_image) : null,
        );
    }
}
