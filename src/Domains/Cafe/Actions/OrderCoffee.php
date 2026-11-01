<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\Actions;

use App\Enums\AcornTransactionKind;
use App\Enums\StudyRoomVenue;
use App\Models\CafeMenuItem;
use App\Models\CafeOrder;
use App\Models\StudyRoomSeat;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use NouTools\Domains\Acorns\Actions\SpendAcorns;
use NouTools\Domains\Acorns\Exceptions\InsufficientAcornsException;
use NouTools\Domains\Cafe\DataTransferObjects\OrderCoffeeData;
use NouTools\Domains\Cafe\Exceptions\DineInNeedsSeatException;
use NouTools\Domains\Cafe\Exceptions\MenuItemUnavailableException;
use NouTools\Domains\Cafe\Exceptions\TakeawayInCafeException;
use NouTools\Domains\Schedules\ValueObjects\StudentScheduleCookie;

/**
 * Buys a coffee with acorns. Only one coffee sits on a student's seat at a
 * time: a new order ends whatever is still on the table. 內用 needs a café
 * seat (and is thrown away when it is left) and is all a café seat can order;
 * 外帶 can be ordered anywhere else and goes with the student. The drink arrives `cafe.coffee.delivery_seconds`
 * after it is ordered. The cup shows on the buyer's seat in whichever room
 * they sit, so that room is told.
 */
final readonly class OrderCoffee
{
    public function __construct(
        private SpendAcorns $spendAcorns,
        private BroadcastCoffeeChange $broadcastCoffeeChange,
    ) {}

    /**
     * @throws MenuItemUnavailableException
     * @throws DineInNeedsSeatException
     * @throws TakeawayInCafeException
     * @throws InsufficientAcornsException
     */
    public function __invoke(StudentScheduleCookie $viewer, OrderCoffeeData $input): CafeOrder
    {
        $item = CafeMenuItem::query()->onMenu()->find($input->menuItemId);

        if ($item === null) {
            throw new MenuItemUnavailableException;
        }

        $holdsCafeSeat = $this->holdsCafeSeat($viewer);

        if (! $input->toGo && ! $holdsCafeSeat) {
            throw new DineInNeedsSeatException;
        }

        if ($input->toGo && $holdsCafeSeat) {
            throw new TakeawayInCafeException;
        }

        $order = DB::transaction(function () use ($viewer, $item, $input): CafeOrder {
            $now = Date::now();

            CafeOrder::query()
                ->where('student_schedule_id', $viewer->id)
                ->where('expires_at', '>', $now)
                ->update(['expires_at' => $now]);

            $order = new CafeOrder;
            $order->student_schedule_id = $viewer->id;
            $order->cafe_menu_item_id = $item->id;
            $order->name = $item->name;
            $order->emoji = $item->emoji;
            $order->cup_style = $item->cup_style;
            $order->to_go = $input->toGo;
            $order->price_paid = $item->price;
            $order->ready_at = $now->copy()->addSeconds((int) config('cafe.coffee.delivery_seconds'));
            $order->expires_at = $now->copy()->addMinutes((int) config('cafe.coffee.duration_minutes'));
            $order->saveOrFail();

            $spend = ($this->spendAcorns)($viewer->id, $item->price, AcornTransactionKind::CoffeePurchase, $order, ['item' => $item->name]);

            $order->acorn_transaction_id = $spend->id;
            $order->saveOrFail();

            return $order;
        });

        ($this->broadcastCoffeeChange)($viewer->id, 'coffee.ordered');

        return $order;
    }

    private function holdsCafeSeat(StudentScheduleCookie $viewer): bool
    {
        return StudyRoomSeat::query()
            ->inRoom(StudyRoomVenue::Cafe)
            ->where('student_schedule_id', $viewer->id)
            ->exists();
    }
}
