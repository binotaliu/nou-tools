<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\DataTransferObjects;

use App\Enums\WikiRevisionOrigin;
use Spatie\LaravelData\Data;

/**
 * Everything a new revision needs, whoever wrote it: the AI flow, an admin
 * in Filament, an import or a revert.
 */
final class PublishWikiRevisionDTO extends Data
{
    /**
     * @param  list<string>  $categories
     */
    public function __construct(
        public string $title,
        public ?string $description,
        public string $body,
        public array $categories,
        public ?string $editSummary,
        public WikiRevisionOrigin $origin,
        public ?int $editRequestId = null,
        public ?int $userId = null,
        public ?int $studentScheduleId = null,
    ) {}
}
