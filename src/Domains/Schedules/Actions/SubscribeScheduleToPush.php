<?php

declare(strict_types=1);

namespace NouTools\Domains\Schedules\Actions;

use App\Models\StudentSchedule;
use NouTools\Domains\Schedules\DataTransferObjects\PushSubscriptionUpsertData;

final class SubscribeScheduleToPush
{
    public function __invoke(StudentSchedule $schedule, PushSubscriptionUpsertData $input): void
    {
        $schedule->updatePushSubscription(
            endpoint: $input->endpoint,
            key: $input->publicKey,
            token: $input->authToken,
            contentEncoding: $input->contentEncoding,
        );
    }
}
