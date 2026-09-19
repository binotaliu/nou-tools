<?php

declare(strict_types=1);

namespace NouTools\Domains\Newsletter\ViewModels;

use App\Enums\NewsletterSection;
use App\Models\NewsletterColumn;
use App\Models\NewsletterIssue;
use App\Models\NewsletterItem;
use Illuminate\Support\Facades\Storage;
use NouTools\Domains\Newsletter\Actions\RenderNewsletterMarkdown;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

final class NewsletterIssueViewModel extends Data
{
    /**
     * @param  DataCollection<int, NewsletterHighlightEventViewModel>  $highlightEvents
     * @param  DataCollection<int, NewsletterItemViewModel>  $newsItems
     * @param  DataCollection<int, NewsletterItemViewModel>  $artItems
     * @param  DataCollection<int, NewsletterItemViewModel>  $centerItems
     * @param  DataCollection<int, NewsletterColumnViewModel>  $columns
     */
    public function __construct(
        public string $issueKey,
        public string $title,
        public ?string $coverImageUrl,
        public ?string $coverImageCreditName,
        public ?string $coverImageCreditUrl,
        public bool $isPublished,
        public string $statusLabel,
        public string $publishesOn,
        public ?string $publishedAt,
        public string $coversFrom,
        public string $coversTo,
        public string $highlightsFrom,
        public string $highlightsTo,
        // Plain HTML string rendered from Markdown; see ArticleIndexPageData::$indexContent.
        public string $highlightsIntro,
        #[DataCollectionOf(NewsletterHighlightEventViewModel::class)]
        public DataCollection $highlightEvents,
        #[DataCollectionOf(NewsletterItemViewModel::class)]
        public DataCollection $newsItems,
        #[DataCollectionOf(NewsletterItemViewModel::class)]
        public DataCollection $artItems,
        #[DataCollectionOf(NewsletterItemViewModel::class)]
        public DataCollection $centerItems,
        #[DataCollectionOf(NewsletterColumnViewModel::class)]
        public DataCollection $columns,
    ) {}

    /**
     * Expects `items` and `columns` to be loaded. Centers' items follow the
     * directory's center order (`$centerOrder`); unlisted sources go last.
     *
     * @param  list<string>  $centerOrder
     */
    public static function fromModel(NewsletterIssue $issue, RenderNewsletterMarkdown $renderMarkdown, array $centerOrder = []): self
    {
        $centerPosition = array_flip($centerOrder);

        $itemsFor = fn (NewsletterSection $section): DataCollection => NewsletterItemViewModel::collect(
            $issue->items
                ->filter(fn (NewsletterItem $item): bool => $item->section === $section)
                ->map(fn (NewsletterItem $item): NewsletterItemViewModel => NewsletterItemViewModel::fromModel($item, $renderMarkdown))
                ->values()
                ->all(),
            DataCollection::class,
        );

        return new self(
            issueKey: $issue->issue_key,
            title: $issue->displayTitle(),
            coverImageUrl: $issue->cover_image !== null ? Storage::disk(NewsletterIssue::COVER_DISK)->url($issue->cover_image) : null,
            coverImageCreditName: $issue->cover_image_credit_name,
            // Unsplash's API guidelines require attribution links to carry utm_source/utm_medium.
            coverImageCreditUrl: $issue->cover_image_credit_url !== null
                ? $issue->cover_image_credit_url.'?utm_source=nou-tools&utm_medium=referral'
                : null,
            isPublished: $issue->isPublished(),
            statusLabel: $issue->status->label(),
            publishesOn: $issue->publishes_on->toDateString(),
            publishedAt: $issue->published_at?->toIso8601String(),
            coversFrom: $issue->covers_from->toDateString(),
            coversTo: $issue->covers_to->toDateString(),
            highlightsFrom: $issue->highlights_from->toDateString(),
            highlightsTo: $issue->highlights_to->toDateString(),
            highlightsIntro: $renderMarkdown($issue->highlights_intro),
            highlightEvents: NewsletterHighlightEventViewModel::collect(
                array_map(NewsletterHighlightEventViewModel::fromSnapshot(...), $issue->highlights_events ?? []),
                DataCollection::class,
            ),
            newsItems: $itemsFor(NewsletterSection::News),
            artItems: $itemsFor(NewsletterSection::Arts),
            centerItems: NewsletterItemViewModel::collect(
                $issue->items
                    ->filter(fn (NewsletterItem $item): bool => $item->section === NewsletterSection::Centers)
                    ->sortBy(fn (NewsletterItem $item): int => $centerPosition[$item->source_name] ?? PHP_INT_MAX)
                    ->map(fn (NewsletterItem $item): NewsletterItemViewModel => NewsletterItemViewModel::fromModel($item, $renderMarkdown))
                    ->values()
                    ->all(),
                DataCollection::class,
            ),
            columns: NewsletterColumnViewModel::collect(
                $issue->columns
                    ->map(fn (NewsletterColumn $column): NewsletterColumnViewModel => NewsletterColumnViewModel::fromModel($column, $renderMarkdown))
                    ->all(),
                DataCollection::class,
            ),
        );
    }
}
