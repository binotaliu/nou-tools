<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\PageData;

use Illuminate\Support\HtmlString;
use NouTools\Domains\StudyRoom\ViewModels\StudyRoomProfileViewModel;
use NouTools\Domains\StudyRoom\ViewModels\StudyRoomStateViewModel;
use NouTools\Domains\StudyRoom\ViewModels\StudyRoomSubjectViewModel;
use NouTools\Domains\StudyRoom\ViewModels\StudyRoomVerbViewModel;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Resource;

final class StudyRoomPageData extends Resource
{
    /**
     * @param  array<int, string>  $emojiChoices
     * @param  array<string, int|float>  $clientConfig
     */
    public function __construct(
        public StudyRoomStateViewModel $roomState,
        public StudyRoomProfileViewModel $profile,
        #[DataCollectionOf(StudyRoomSubjectViewModel::class)]
        public DataCollection $subjects,
        #[DataCollectionOf(StudyRoomVerbViewModel::class)]
        public DataCollection $verbs,
        public HtmlString $announcementHtml,
        public string $openHoursLabel,
        public bool $hasSchedule,
        public bool $needsProfile,
        public array $emojiChoices,
        public bool $isOpen,
        public array $clientConfig,
        public ?string $vapidPublicKey,
    ) {}
}
