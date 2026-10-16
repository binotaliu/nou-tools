<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\PageData;

use NouTools\Domains\Wiki\ViewModels\WikiEditRequestViewModel;
use Spatie\LaravelData\Resource;

final class WikiEditRequestPageData extends Resource
{
    public function __construct(
        public WikiEditRequestViewModel $request,
        public int $instructionMaxLength,
    ) {}
}
