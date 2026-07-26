<?php

declare(strict_types=1);

namespace NouTools\Domains\DiscountStores\DataTransferObjects;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

final class ReportDiscountStoreDTO extends Data
{
    public function __construct(
        #[Required, MapInputName('is_valid')]
        public bool $isValid,
        public ?string $comment = null,
    ) {}
}
