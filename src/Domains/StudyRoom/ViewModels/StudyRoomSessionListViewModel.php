<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\ViewModels;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

final class StudyRoomSessionListViewModel extends Data
{
    public function __construct(
        #[DataCollectionOf(StudyRoomSessionViewModel::class)]
        public DataCollection $sessions,
    ) {}
}
