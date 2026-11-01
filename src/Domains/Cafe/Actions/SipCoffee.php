<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\Actions;

use App\Models\CafeOrder;
use Illuminate\Support\Facades\Date;
use NouTools\Domains\Cafe\Exceptions\NoCoffeeToHandleException;
use NouTools\Domains\Schedules\ValueObjects\StudentScheduleCookie;

/**
 * One sip from the cup on the table. The increment is a conditional UPDATE so
 * two taps in a row can't take more sips than the cup holds.
 */
final readonly class SipCoffee
{
    public function __construct(private BroadcastCoffeeChange $broadcastCoffeeChange) {}

    /**
     * @throws NoCoffeeToHandleException
     */
    public function __invoke(StudentScheduleCookie $viewer): CafeOrder
    {
        $now = Date::now();

        $order = CafeOrder::query()
            ->where('student_schedule_id', $viewer->id)
            ->where('expires_at', '>', $now)
            ->latest('id')
            ->first();

        if ($order === null) {
            throw new NoCoffeeToHandleException;
        }

        if ($order->ready_at !== null && $order->ready_at->isAfter($now)) {
            throw new NoCoffeeToHandleException('飲品還在製作中，請稍等一下。');
        }

        $affected = CafeOrder::query()
            ->whereKey($order->id)
            ->where('sips_taken', '<', (int) config('cafe.coffee.sips'))
            ->increment('sips_taken');

        if ($affected === 0) {
            throw new NoCoffeeToHandleException('杯子已經空了。');
        }

        ($this->broadcastCoffeeChange)($viewer->id, 'coffee.sipped');

        return $order->refresh();
    }
}
