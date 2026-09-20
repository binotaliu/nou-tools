<?php

declare(strict_types=1);

namespace NouTools\Domains\Schedules\Actions;

use App\Models\StudentSchedule;
use Illuminate\Support\Facades\DB;
use NouTools\Domains\Schedules\DataTransferObjects\PushSubscriptionUpsertData;

final readonly class EnableClassStartingReminders
{
    public function __construct(private SubscribeScheduleToPush $subscribeScheduleToPush) {}

    public function __invoke(StudentSchedule $schedule, PushSubscriptionUpsertData $input): void
    {
        DB::transaction(function () use ($schedule, $input): void {
            ($this->subscribeScheduleToPush)($schedule, $input);

            $schedule->notify_on_class_start = true;
            $schedule->saveOrFail();
        });
    }
}
