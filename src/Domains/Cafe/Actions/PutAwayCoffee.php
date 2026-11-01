<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\Actions;

use App\Models\CafeOrder;
use Illuminate\Support\Facades\Date;
use NouTools\Domains\Cafe\Exceptions\NoCoffeeToHandleException;
use NouTools\Domains\Schedules\ValueObjects\StudentScheduleCookie;

/**
 * Clears the cup off the table early. Nothing is refunded.
 */
final readonly class PutAwayCoffee
{
    public function __construct(private BroadcastCoffeeChange $broadcastCoffeeChange) {}

    /**
     * @throws NoCoffeeToHandleException
     */
    public function __invoke(StudentScheduleCookie $viewer): void
    {
        $now = Date::now();

        $affected = CafeOrder::query()
            ->where('student_schedule_id', $viewer->id)
            ->where('expires_at', '>', $now)
            ->update(['expires_at' => $now]);

        if ($affected === 0) {
            throw new NoCoffeeToHandleException('桌上沒有飲品。');
        }

        ($this->broadcastCoffeeChange)($viewer->id, 'coffee.removed');
    }
}
