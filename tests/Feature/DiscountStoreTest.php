<?php

use App\Enums\DiscountStoreStatus;
use App\Enums\DiscountStoreType;
use App\Models\DiscountStore;
use App\Models\DiscountStoreCategory;
use App\Models\DiscountStoreComment;
use App\Models\DiscountStoreReport;
use App\Notifications\NewDiscountStoreComment;
use App\Notifications\NewDiscountStoreReport;
use App\Notifications\NewPendingDiscountStore;
use Coderflex\LaravelTurnstile\Facades\LaravelTurnstile;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\get;
use function Pest\Laravel\post;

beforeEach(function () {
    $this->category = DiscountStoreCategory::factory()->create();
});

it('displays the discount store index page', function () {
    $store = DiscountStore::factory()
        ->for($this->category, 'category')
        ->create([
            'name' => '測試優惠店家',
            'status' => DiscountStoreStatus::Online,
            'type' => DiscountStoreType::Online,
        ]);

    $response = get(route('discount-stores.index'));

    $response->assertSuccessful();
    $response->assertSee('優惠店家');
    $response->assertSee('測試優惠店家');
    $response->assertSee(route('discount-stores.show', $store), false);
    $response->assertDontSee('回報有效');
    $response->assertDontSee('留言（審核後顯示）');
});

it('displays the discount store detail page and adds noindex robots meta', function () {
    $store = DiscountStore::factory()
        ->for($this->category, 'category')
        ->create([
            'name' => '詳細頁店家',
            'status' => DiscountStoreStatus::Online,
            'discount_details' => '詳細優惠內容',
        ]);

    DiscountStoreComment::factory()->for($store, 'store')->create([
        'nickname' => '學生小芳',
        'content' => '這個優惠很棒',
        'is_approved' => true,
    ]);

    DiscountStoreComment::factory()->for($store, 'store')->create([
        'nickname' => '匿名',
        'content' => '這是未審核留言',
        'is_approved' => false,
    ]);

    $response = get(route('discount-stores.show', $store));

    $response->assertSuccessful();
    $response->assertSee('詳細頁店家');
    $response->assertSee('詳細優惠內容');
    $response->assertSee('最新回報');
    $response->assertSee('目前還沒有回報資料。');
    $response->assertSee('留言（審核後顯示）');
    $response->assertSee('學生小芳');
    $response->assertSee('這個優惠很棒');
    $response->assertDontSee('這是未審核留言');
    $response->assertSee('<meta name="robots" content="noindex, nofollow" />', false);
});

it('shows latest report details and expandable recent report list on store detail page', function () {
    $store = DiscountStore::factory()
        ->for($this->category, 'category')
        ->create([
            'status' => DiscountStoreStatus::Online,
        ]);

    DiscountStoreReport::factory()->for($store, 'store')->create([
        'is_valid' => true,
        'comment' => '最舊回報',
        'created_at' => now()->subHours(3),
    ]);

    DiscountStoreReport::factory()->for($store, 'store')->create([
        'is_valid' => false,
        'comment' => '較新回報',
        'created_at' => now()->subHours(2),
    ]);

    DiscountStoreReport::factory()->for($store, 'store')->create([
        'is_valid' => true,
        'comment' => '最新回報內容',
        'created_at' => now()->subHour(),
    ]);

    $response = get(route('discount-stores.show', $store));

    $response->assertSuccessful();
    $response->assertSee('最新回報');
    $response->assertSee('最新回報內容');
    $response->assertSee('展開看更多近期回報（2）');
    $response->assertSee('較新回報');
    $response->assertSee('最舊回報');
});

it('returns not found when opening a non-online discount store detail page', function () {
    $store = DiscountStore::factory()
        ->for($this->category, 'category')
        ->create([
            'status' => DiscountStoreStatus::Pending,
        ]);

    get(route('discount-stores.show', $store))->assertNotFound();
});

it('only shows online stores', function () {
    DiscountStore::factory()
        ->for($this->category, 'category')
        ->create([
            'name' => '上線店家',
            'status' => DiscountStoreStatus::Online,
        ]);

    DiscountStore::factory()
        ->for($this->category, 'category')
        ->create([
            'name' => '待審核店家',
            'status' => DiscountStoreStatus::Pending,
        ]);

    DiscountStore::factory()
        ->for($this->category, 'category')
        ->create([
            'name' => '已過期店家',
            'status' => DiscountStoreStatus::Expired,
        ]);

    $response = get(route('discount-stores.index'));

    $response->assertSee('上線店家');
    $response->assertDontSee('待審核店家');
    $response->assertDontSee('已過期店家');
});

it('initializes filter state from query parameters', function () {
    DiscountStore::factory()
        ->for($this->category, 'category')
        ->create([
            'name' => 'Spotify 學生方案',
            'status' => DiscountStoreStatus::Online,
            'type' => DiscountStoreType::Online,
            'city' => '臺北市',
        ]);

    $response = get(route('discount-stores.index', [
        'search' => 'Spotify',
        'category' => $this->category->id,
        'type' => 'online',
        'city' => '臺北市',
    ]));

    $response->assertSee('discountStoreIndex(');
    $response->assertSee('initialSearch');
    $response->assertSee('Spotify');
    $response->assertSee('initialCategory');
    $response->assertSee((string) $this->category->id);
    $response->assertSee('initialType');
    $response->assertSee('online');
    $response->assertSee('initialCity');
    $response->assertSee('臺北市');
});

it('displays the create discount store page', function () {
    $response = get(route('discount-stores.create'));

    $response->assertSuccessful();
    $response->assertSee('新增優惠店家');
    $response->assertDontSee('livewire:submit-discount-store-form');
});

it('submits a new discount store with the web form', function () {
    Notification::fake();

    LaravelTurnstile::shouldReceive('validate')
        ->once()
        ->andReturn(['success' => true]);

    $response = post(route('discount-stores.store'), [
        'name' => '測試新店家',
        'type' => DiscountStoreType::Online->value,
        'category_id' => $this->category->id,
        'city' => '臺北市',
        'district' => '中正區',
        'address' => 'https://example.com',
        'verification_method' => '學生證',
        'discount_details' => '9 折優惠',
        'notes' => '測試備註',
        'cf-turnstile-response' => 'test-token',
    ]);

    $response->assertRedirect(route('discount-stores.create'));
    $response->assertSessionHas('success');

    $store = DiscountStore::query()->where('name', '測試新店家')->first();

    expect($store)->not->toBeNull();
    expect($store?->status)->toBe(DiscountStoreStatus::Pending);

    Notification::assertSentTo(
        new AnonymousNotifiable,
        NewPendingDiscountStore::class,
        function (NewPendingDiscountStore $notification): bool {
            return true;
        },
    );
});

it('submits a discount store report with the web form', function () {
    Notification::fake();

    $store = DiscountStore::factory()
        ->for($this->category, 'category')
        ->create([
            'status' => DiscountStoreStatus::Online,
        ]);

    LaravelTurnstile::shouldReceive('validate')
        ->once()
        ->andReturn(['success' => true]);

    $response = post(route('discount-stores.reports.store', $store), [
        'is_valid' => '0',
        'comment' => '這家已經沒有學生優惠了',
        'cf-turnstile-response' => 'test-token',
    ]);

    $response->assertRedirect(route('discount-stores.show', $store));
    $response->assertSessionHas('success');

    $report = DiscountStoreReport::query()
        ->where('store_id', $store->id)
        ->where('is_valid', false)
        ->first();

    expect($report)->not->toBeNull();
    expect($report?->comment)->toBe('這家已經沒有學生優惠了');

    Notification::assertSentTo(
        new AnonymousNotifiable,
        NewDiscountStoreReport::class,
        function (NewDiscountStoreReport $notification): bool {
            return true;
        },
    );
});

it('submits a store comment with the web form', function () {
    Notification::fake();

    $store = DiscountStore::factory()
        ->for($this->category, 'category')
        ->create([
            'status' => DiscountStoreStatus::Online,
        ]);

    LaravelTurnstile::shouldReceive('validate')
        ->once()
        ->andReturn(['success' => true]);

    $response = post(route('discount-stores.comments.store', $store), [
        'nickname' => '學生小明',
        'content' => '這家店優惠還在',
        'cf-turnstile-response' => 'test-token',
    ]);

    $response->assertRedirect(route('discount-stores.show', $store));
    $response->assertSessionHas('success');

    $comment = DiscountStoreComment::query()
        ->where('store_id', $store->id)
        ->where('nickname', '學生小明')
        ->where('content', '這家店優惠還在')
        ->first();

    expect($comment)->not->toBeNull();
    expect($comment?->is_approved)->toBeFalse();

    Notification::assertSentTo(
        new AnonymousNotifiable,
        NewDiscountStoreComment::class,
        function (NewDiscountStoreComment $notification) use ($store) {
            return $notification->toArray(new class
            {
                public function routeNotificationFor($channel)
                {
                    return config('services.discord.webhooks.new_comment');
                }
            })['store_id'] === $store->id;
        },
    );
});

it('shows approved comments on store detail page', function () {
    $store = DiscountStore::factory()
        ->for($this->category, 'category')
        ->create([
            'status' => DiscountStoreStatus::Online,
        ]);

    DiscountStoreComment::factory()->for($store, 'store')->create([
        'nickname' => '學生小芳',
        'content' => '這個優惠很棒',
        'is_approved' => true,
    ]);

    DiscountStoreComment::factory()->for($store, 'store')->create([
        'nickname' => '匿名',
        'content' => '這是未審核留言',
        'is_approved' => false,
    ]);

    $response = get(route('discount-stores.show', $store));

    $response->assertSee('學生小芳');
    $response->assertSee('這個優惠很棒');
    $response->assertDontSee('這是未審核留言');
});

it('shows empty state when no stores match', function () {
    $response = get(route('discount-stores.index'));

    $response->assertSee('目前沒有符合條件的優惠店家');
});
