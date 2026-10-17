<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\PageData;

use NouTools\Domains\Wiki\ViewModels\WikiPageLinkViewModel;
use Spatie\LaravelData\Resource;

/**
 * The 提出修改 / 建立新條目 form.
 */
final class WikiEditRequestFormPageData extends Resource
{
    /**
     * @param  list<string>  $sourceDomains
     */
    public function __construct(
        public ?WikiPageLinkViewModel $page,
        public ?string $requestedSlug,
        public ?string $requestedTitle,
        public bool $signedIn,
        public bool $blocked,
        public bool $isProtected,
        public ?string $turnstileSiteKey,
        public int $instructionMaxLength,
        public int $maxReferences,
        public int $screenshotMaxKb,
        public array $sourceDomains,
    ) {}
}
