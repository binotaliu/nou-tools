<?php

declare(strict_types=1);

namespace NouTools\Domains\DiscountStores\DataTransferObjects;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

final class SubmitDiscountStoreDTO extends Data
{
    public function __construct(
        #[Required, Max(255)]
        public string $name,
        #[Required]
        public string $type,
        #[Required, MapInputName('category_id')]
        public int $categoryId,
        #[Max(50)]
        public ?string $city = null,
        #[Max(50)]
        public ?string $district = null,
        #[Max(500)]
        public string $address = '',
        #[Max(255), MapInputName('verification_method')]
        public string $verificationMethod = '',
        #[Required, MapInputName('discount_details')]
        public string $discountDetails = '',
        public ?string $notes = null,
        #[MapInputName('tested_valid')]
        public bool $testedValid = false,
    ) {}
}
