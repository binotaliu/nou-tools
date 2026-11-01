<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\Actions;

use App\Enums\AcornTransactionKind;
use App\Models\CafeMenuItem;
use App\Models\CafeOrder;
use App\Models\CafeSeat;
use App\Models\StudyRoomSeat;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use NouTools\Domains\Acorns\Actions\SpendAcorns;
use NouTools\Domains\Acorns\Exceptions\InsufficientAcornsException;
use NouTools\Domains\Cafe\DataTransferObjects\OrderCoffeeData;
use NouTools\Domains\Cafe\Exceptions\MenuItemUnavailableException;
use NouTools\Domains\Schedules\ValueObjects\StudentScheduleCookie;
use NouTools\Domains\StudyRoom\Actions\BroadcastStudyRoomChange;

/**
 * Buys a coffee with acorns. Only one coffee sits on a student's seat at a
 * time: a new order ends whatever is still on the table. The cup shows on the
 * buyer's café seat and study-room seat, so both rooms are told.
 */
final readonly class OrderCoffee
{
    public function __construct(
        private SpendAcorns $spendAcorns,
        private BroadcastCafeChange $broadcastCafeChange,
        private BroadcastStudyRoomChange $broadcastStudyRoomChange,
    ) {}

    /**
     * @throws MenuItemUnavailableException
     * @throws InsufficientAcornsException
     */
    public function __invoke(StudentScheduleCookie $viewer, OrderCoffeeData $input): CafeOrder
    {
        $item = CafeMenuItem::query()->onMenu()->find($input->menuItemId);

        if ($item === null) {
            throw new MenuItemUnavailableException;
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
            $order->expires_at = $now->copy()->addMinutes((int) config('cafe.coffee.duration_minutes'));
            $order->saveOrFail();

            $spend = ($this->spendAcorns)($viewer->id, $item->price, AcornTransactionKind::CoffeePurchase, $order, ['item' => $item->name]);

            $order->acorn_transaction_id = $spend->id;
            $order->saveOrFail();

            return $order;
        });

        $cafeSeat = CafeSeat::query()->where('student_schedule_id', $viewer->id)->first();

        if ($cafeSeat !== null) {
            ($this->broadcastCafeChange)('coffee.ordered', $cafeSeat);
        }

        $studySeat = StudyRoomSeat::query()->where('student_schedule_id', $viewer->id)->first();

        if ($studySeat !== null) {
            ($this->broadcastStudyRoomChange)('seat.coffee', $studySeat);
        }

        return $order;
    }
}
