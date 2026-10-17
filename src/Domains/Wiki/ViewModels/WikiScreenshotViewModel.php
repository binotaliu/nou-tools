<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\ViewModels;

use App\Models\WikiEditRequest;
use Spatie\LaravelData\Data;

/**
 * A screenshot a visitor attached to an edit request.
 */
final class WikiScreenshotViewModel extends Data
{
    public function __construct(
        public string $name,
        public string $url,
    ) {}

    /**
     * @return list<WikiScreenshotViewModel>
     */
    public static function listFor(WikiEditRequest $editRequest): array
    {
        return array_values(array_map(
            fn (array $screenshot, int $index): self => new self(
                name: $screenshot['name'],
                url: route('wiki.edit-requests.screenshots.show', ['editRequest' => $editRequest, 'index' => $index]),
            ),
            $editRequest->screenshots ?? [],
            array_keys($editRequest->screenshots ?? []),
        ));
    }
}
