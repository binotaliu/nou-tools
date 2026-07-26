<?php

declare(strict_types=1);

namespace NouTools\Domains\Directory\ViewModels;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

final class CenterItemViewModel extends Data
{
    /**
     * @param  DataCollection<int, PhoneNumberViewModel>  $phone
     */
    public function __construct(
        public string $name,
        public string $url,
        public string $region,
        public string $regionLabel,
        public string $address,
        #[DataCollectionOf(PhoneNumberViewModel::class)]
        public DataCollection $phone,
        public float $latitude,
        public float $longitude,
        public ?string $transportUrl = null,
        public ?string $googleMapsUrl = null,
    ) {}
}
