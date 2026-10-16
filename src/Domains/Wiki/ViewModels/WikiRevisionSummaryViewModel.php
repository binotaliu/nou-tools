<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\ViewModels;

use App\Models\WikiRevision;
use Spatie\LaravelData\Data;

/**
 * One line of a page history or of 最近更新.
 */
final class WikiRevisionSummaryViewModel extends Data
{
    public function __construct(
        public int $id,
        public string $pageSlug,
        public string $title,
        public ?string $editSummary,
        public string $origin,
        public string $originLabel,
        public string $createdAt,
        public bool $isCurrent,
        public string $url,
    ) {}

    public static function fromModel(WikiRevision $revision, string $pageSlug, ?int $currentRevisionId): self
    {
        return new self(
            id: $revision->id,
            pageSlug: $pageSlug,
            title: $revision->title,
            editSummary: $revision->edit_summary,
            origin: $revision->origin->value,
            originLabel: $revision->origin->label(),
            createdAt: $revision->created_at->toIso8601String(),
            isCurrent: $revision->id === $currentRevisionId,
            url: route('wiki.revision', ['slug' => $pageSlug, 'revision' => $revision->id]),
        );
    }
}
