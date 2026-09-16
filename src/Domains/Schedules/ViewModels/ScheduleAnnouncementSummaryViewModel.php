<?php

declare(strict_types=1);

namespace NouTools\Domains\Schedules\ViewModels;

use App\Models\Announcement;
use Spatie\LaravelData\Data;

final class ScheduleAnnouncementSummaryViewModel extends Data
{
    public function __construct(
        public string $sourceName,
        public string $category,
        public string $title,
        public string $url,
        public bool $publishedAt,
        public ?string $publishedRelativeLabel,
        public ?string $publishedDateDisplay,
    ) {}

    public static function fromModel(Announcement $announcement): self
    {
        $publishedAt = $announcement->published_at;

        $relativeLabel = match (true) {
            $publishedAt === null => null,
            $publishedAt->isToday() => '今天',
            $publishedAt->isYesterday() => '昨天',
            $publishedAt->isTomorrow() => '明天',
            default => $publishedAt->diffForHumans(),
        };

        return new self(
            sourceName: (string) $announcement->source_name,
            category: (string) $announcement->category,
            title: (string) $announcement->title,
            url: (string) $announcement->url,
            publishedAt: $publishedAt !== null,
            publishedRelativeLabel: $relativeLabel,
            publishedDateDisplay: $publishedAt?->format('Y/m/d'),
        );
    }
}
