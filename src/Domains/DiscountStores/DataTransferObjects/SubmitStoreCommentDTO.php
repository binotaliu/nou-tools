<?php

declare(strict_types=1);

namespace NouTools\Domains\DiscountStores\DataTransferObjects;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

final class SubmitStoreCommentDTO extends Data
{
    public function __construct(
        #[Required, Max(100)]
        public string $nickname,

        #[Required, Max(1000)]
        public string $content,
    ) {}
}
