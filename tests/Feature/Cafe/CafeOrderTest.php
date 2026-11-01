<?php

use App\Enums\AcornTransactionKind;
use App\Events\CafeUpdated;
use App\Events\StudyRoomUpdated;
use App\Models\AcornTransaction;
use App\Models\CafeMenuItem;
use App\Models\CafeOrder;
use App\Models\StudentSchedule;
use App\Models\StudyRoomProfile;
use App\Models\StudyRoomSeat;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;
use NouTools\Domains\Acorns\Actions\ReadAcornBalance;
use NouTools\Domains\StudyRoom\Actions\BuildStudyRoomState;

$orderCookie = function (StudentSchedule $schedule): string {
    return json_encode(['id' => $schedule->id, 'uuid' => $schedule->uuid, 'name' => $schedule->name]);
};

$richStudent = function (int $acorns = 10): StudentSchedule {
    $schedule = StudentSchedule::factory()->create();
    StudyRoomProfile::factory()->for($schedule, 'schedule')->create(['nickname' => '點餐同學']);
    AcornTransaction::factory()->for($schedule, 'schedule')->credit($acorns)->create();

    return $schedule;
};

it('lists the seeded menu in order and hides inactive items', function () {
    CafeMenuItem::factory()->create(['name' => '停售飲品', 'is_active' => false]);

    $names = collect($this->getJson(route('cafe.menu'))->assertOk()->json('menu'))->pluck('name');

    expect($names->first())->toBe('美式咖啡')
        ->and($names)->toHaveCount(5)
        ->and($names)->not->toContain('停售飲品');
});

it('sells a dine-in coffee to someone in the café and puts the cup on their seat after a delay', function () use ($orderCookie, $richStudent) {
    Event::fake([CafeUpdated::class, StudyRoomUpdated::class]);
    config()->set('cafe.coffee.delivery_seconds', 6);
    $schedule = $richStudent(10);
    $cafeSeat = StudyRoomSeat::factory()->cafe()->occupiedBy($schedule)->create();
    $latte = CafeMenuItem::query()->where('name', '拿鐵')->firstOrFail();

    $this->withCredentials()->withCookie('student_schedule', $orderCookie($schedule))
        ->postJson(route('cafe.orders.store'), ['menuItemId' => $latte->id])
        ->assertOk()
        ->assertJsonPath('coffee.name', '拿鐵')
        ->assertJsonPath('coffee.toGo', false)
        ->assertJsonPath('acorns.balance', 7);

    $order = CafeOrder::query()->firstOrFail();
    expect($order->price_paid)->toBe(3)
        ->and($order->acorn_transaction_id)->not->toBeNull()
        ->and($order->ready_at->diffInSeconds($order->created_at, true))->toBeGreaterThanOrEqual(5)
        ->and(AcornTransaction::query()->where('kind', AcornTransactionKind::CoffeePurchase)->value('amount'))->toBe(-3);

    $cafeState = $this->getJson(route('cafe.state'))->json('seats');
    $coffee = collect($cafeState)->firstWhere('code', $cafeSeat->code)['coffee'];
    expect($coffee['name'])->toBe('拿鐵')
        ->and($coffee['level'])->toBe(1);

    Event::assertDispatched(CafeUpdated::class, fn (CafeUpdated $event): bool => $event->type === 'coffee.ordered');
    Event::assertNotDispatched(StudyRoomUpdated::class);
});

it('lets a takeaway coffee be ordered anywhere and shows it on the study-room seat', function () use ($orderCookie, $richStudent) {
    Event::fake([CafeUpdated::class, StudyRoomUpdated::class]);
    $schedule = $richStudent(10);
    $studySeat = StudyRoomSeat::factory()->occupiedBy($schedule)->create();
    $latte = CafeMenuItem::query()->where('name', '拿鐵')->firstOrFail();

    $this->withCredentials()->withCookie('student_schedule', $orderCookie($schedule))
        ->postJson(route('cafe.orders.store'), ['menuItemId' => $latte->id, 'toGo' => true])
        ->assertOk()
        ->assertJsonPath('coffee.toGo', true);

    $studyState = app(BuildStudyRoomState::class)(null)->toArray();
    $studySeatState = collect($studyState['floors'])
        ->flatMap(fn (array $floor): array => $floor['soloSeats'])
        ->firstWhere('code', $studySeat->code);
    expect($studySeatState['coffee']['name'])->toBe('拿鐵');

    Event::assertDispatched(StudyRoomUpdated::class, fn (StudyRoomUpdated $event): bool => $event->type === 'seat.coffee');
    Event::assertNotDispatched(CafeUpdated::class);
});

it('needs a café seat for dine-in but not for takeaway', function () use ($orderCookie, $richStudent) {
    $schedule = $richStudent(10);
    $cookie = $orderCookie($schedule);
    $item = CafeMenuItem::query()->firstOrFail();

    $this->withCredentials()->withCookie('student_schedule', $cookie)
        ->postJson(route('cafe.orders.store'), ['menuItemId' => $item->id])
        ->assertStatus(422)
        ->assertJsonPath('message', '內用要先在咖啡廳入座喔，或改點外帶。');

    // A study-room seat is not a café seat.
    $studySeat = StudyRoomSeat::factory()->occupiedBy($schedule)->create();
    $this->withCredentials()->withCookie('student_schedule', $cookie)
        ->postJson(route('cafe.orders.store'), ['menuItemId' => $item->id, 'toGo' => false])
        ->assertStatus(422);

    expect(CafeOrder::query()->count())->toBe(0)
        ->and(app(ReadAcornBalance::class)($schedule->id)->balance)->toBe(10);

    $this->withCredentials()->withCookie('student_schedule', $cookie)
        ->postJson(route('cafe.orders.store'), ['menuItemId' => $item->id, 'toGo' => true])
        ->assertOk();
});

it('takes sips until the cup is empty, but not before the drink arrives', function () use ($orderCookie, $richStudent) {
    config()->set('cafe.coffee.sips', 2);
    $schedule = $richStudent(10);
    $cookie = $orderCookie($schedule);
    StudyRoomSeat::factory()->cafe()->occupiedBy($schedule)->create();
    CafeOrder::factory()->for($schedule, 'schedule')->create(['ready_at' => Date::now()->addSeconds(5)]);

    $this->withCredentials()->withCookie('student_schedule', $cookie)
        ->postJson(route('cafe.orders.current.sip'))->assertStatus(422);

    Date::setTestNow(Date::now()->addSeconds(10));

    $this->withCredentials()->withCookie('student_schedule', $cookie)
        ->postJson(route('cafe.orders.current.sip'))
        ->assertOk()->assertJsonPath('coffee.level', 0.5);
    $this->withCredentials()->withCookie('student_schedule', $cookie)
        ->postJson(route('cafe.orders.current.sip'))
        ->assertOk()->assertJsonPath('coffee.level', 0);
    $this->withCredentials()->withCookie('student_schedule', $cookie)
        ->postJson(route('cafe.orders.current.sip'))
        ->assertStatus(422)->assertJsonPath('message', '杯子已經空了。');

    expect(CafeOrder::query()->firstOrFail()->sips_taken)->toBe(2);
});

it('puts the cup away without a refund', function () use ($orderCookie, $richStudent) {
    $schedule = $richStudent(10);
    $cookie = $orderCookie($schedule);
    StudyRoomSeat::factory()->cafe()->occupiedBy($schedule)->create();
    CafeOrder::factory()->for($schedule, 'schedule')->create();

    $this->withCredentials()->withCookie('student_schedule', $cookie)
        ->deleteJson(route('cafe.orders.current.destroy'))->assertOk();

    expect($schedule->fresh()->activeCafeOrder)->toBeNull();

    $this->withCredentials()->withCookie('student_schedule', $cookie)
        ->deleteJson(route('cafe.orders.current.destroy'))->assertStatus(422);
});

it('replaces the coffee still on the table and lets an old one run out', function () use ($orderCookie, $richStudent) {
    $schedule = $richStudent(10);
    StudyRoomSeat::factory()->cafe()->occupiedBy($schedule)->create();
    $menu = CafeMenuItem::query()->orderBy('id')->get();
    $cookie = $orderCookie($schedule);

    foreach ([$menu[0], $menu[1]] as $item) {
        $this->withCredentials()->withCookie('student_schedule', $cookie)
            ->postJson(route('cafe.orders.store'), ['menuItemId' => $item->id])->assertOk();
    }

    expect($schedule->fresh()->activeCafeOrder->name)->toBe('拿鐵')
        ->and(CafeOrder::query()->where('expires_at', '>', Date::now())->count())->toBe(1);

    Date::setTestNow(Date::now()->addHours(3));

    expect($schedule->fresh()->activeCafeOrder)->toBeNull();
});

it('refuses when acorns are short, the item is off the menu or there is no nickname', function () use ($orderCookie, $richStudent) {
    $poor = $richStudent(2);
    StudyRoomSeat::factory()->cafe()->occupiedBy($poor)->create();
    $item = CafeMenuItem::query()->firstOrFail();

    $this->withCredentials()->withCookie('student_schedule', $orderCookie($poor))
        ->postJson(route('cafe.orders.store'), ['menuItemId' => $item->id])
        ->assertStatus(422)->assertJsonPath('message', '橡實不夠囉。');

    $off = CafeMenuItem::factory()->create(['is_active' => false]);
    $rich = $richStudent(10);
    StudyRoomSeat::factory()->cafe()->occupiedBy($rich)->create();

    $this->withCredentials()->withCookie('student_schedule', $orderCookie($rich))
        ->postJson(route('cafe.orders.store'), ['menuItemId' => $off->id])->assertStatus(422);

    $nameless = StudentSchedule::factory()->create();
    $this->withCredentials()->withCookie('student_schedule', $orderCookie($nameless))
        ->postJson(route('cafe.orders.store'), ['menuItemId' => $item->id])->assertForbidden();

    expect(CafeOrder::query()->count())->toBe(0)
        ->and(app(ReadAcornBalance::class)($poor->id)->balance)->toBe(2);
});

it('only lets someone sitting in the café order dine-in', function () use ($orderCookie, $richStudent) {
    $schedule = $richStudent(10);
    StudyRoomSeat::factory()->cafe()->occupiedBy($schedule)->create();
    $item = CafeMenuItem::query()->firstOrFail();

    $this->withCredentials()->withCookie('student_schedule', $orderCookie($schedule))
        ->postJson(route('cafe.orders.store'), ['menuItemId' => $item->id, 'toGo' => true])
        ->assertStatus(422)
        ->assertJsonPath('message', '坐在咖啡廳裡只能內用喔。');

    expect(CafeOrder::query()->count())->toBe(0)
        ->and(app(ReadAcornBalance::class)($schedule->id)->balance)->toBe(10);

    $this->withCredentials()->withCookie('student_schedule', $orderCookie($schedule))
        ->postJson(route('cafe.orders.store'), ['menuItemId' => $item->id, 'toGo' => false])
        ->assertOk();
});
