<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\Actions;

use App\Enums\StudyActivityVerb;
use NouTools\Domains\StudyRoom\ViewModels\StudyRoomVerbViewModel;

/**
 * The activity verbs a student can pick for a focus session. Shared by the
 * study room and the café, which run the same timer.
 */
final readonly class ListStudyRoomVerbs
{
    /**
     * @return array<int, StudyRoomVerbViewModel>
     */
    public function __invoke(): array
    {
        return array_map(
            fn (StudyActivityVerb $verb): StudyRoomVerbViewModel => new StudyRoomVerbViewModel(
                value: $verb->value,
                label: $verb->label(),
            ),
            StudyActivityVerb::cases(),
        );
    }
}
