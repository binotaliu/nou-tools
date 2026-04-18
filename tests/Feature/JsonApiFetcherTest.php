<?php

use App\Models\Announcement;
use Illuminate\Support\Facades\Http;
use NouTools\Domains\Announcements\Actions\SyncAnnouncements;
use NouTools\Domains\Announcements\DataTransferObjects\AnnouncementSourceConfigDTO;
use NouTools\Domains\Announcements\Fetchers\JsonApiFetcher;

function jsonApiSourceConfig(array $overrides = []): AnnouncementSourceConfigDTO
{
    return AnnouncementSourceConfigDTO::fromConfig(
        $overrides['key'] ?? 'json-api-source',
        array_merge([
            'name' => '教務處',
            'category' => '註冊選課',
            'fetch_url' => 'https://example.com/api',
            'fetcher_type' => 'json_api',
            'fetcher_config' => ['base_url' => 'https://example.com'],
            'tracks_expiry' => true,
            'is_active' => true,
        ], $overrides),
    );
}

it('parses JSON API response into fetched DTOs', function () {
    $source = jsonApiSourceConfig([
        'fetch_url' => 'https://studadm.nou.edu.tw/api/AdvertApi?CategoryId=12&Page=1&take=999',
        'fetcher_config' => ['base_url' => 'https://studadm.nou.edu.tw'],
    ]);

    Http::fake([
        'studadm.nou.edu.tw/*' => Http::response([
            'CategoryId' => 12,
            'CategoryName' => '註冊選課',
            'Adverts' => [
                [
                    'AdvertID' => 7397,
                    'Title' => '專科部各科課程一覽表',
                    'Tags' => '114下',
                    'Url' => '/FileUploads/File/7397/test.pdf',
                    'File' => '/FileUploads/File/7397/test.pdf',
                    'StartDateTime' => '2025-12-05 08:47:35',
                ],
                [
                    'AdvertID' => 7383,
                    'Title' => '舊生選課注意事項',
                    'Tags' => '114下',
                    'Url' => '/FileUploads/File/7383/test.pdf',
                    'File' => '/FileUploads/File/7383/test.pdf',
                    'StartDateTime' => '2025-11-25 08:53:35',
                ],
            ],
        ]),
    ]);

    $fetcher = new JsonApiFetcher;
    $results = $fetcher->fetch($source);

    expect($results)->toHaveCount(2)
        ->and($results[0]->sourceId)->toBe('7397')
        ->and($results[0]->title)->toBe('專科部各科課程一覽表')
        ->and($results[0]->url)->toBe('https://studadm.nou.edu.tw/FileUploads/File/7397/test.pdf')
        ->and($results[0]->tags)->toBe(['114下'])
        ->and($results[0]->publishedAt)->not->toBeNull()
        ->and($results[1]->sourceId)->toBe('7383')
        ->and($results[1]->title)->toBe('舊生選課注意事項');
});

it('syncs new announcements from JSON API source', function () {
    $source = jsonApiSourceConfig();

    Http::fake([
        'example.com/*' => Http::response([
            'Adverts' => [
                [
                    'AdvertID' => 100,
                    'Title' => '公告一',
                    'Tags' => '標籤A,標籤B',
                    'Url' => '/article/100',
                    'File' => null,
                    'StartDateTime' => '2026-01-01 10:00:00',
                ],
                [
                    'AdvertID' => 200,
                    'Title' => '公告二',
                    'Tags' => null,
                    'Url' => '/article/200',
                    'File' => null,
                    'StartDateTime' => null,
                ],
            ],
        ]),
    ]);

    $syncAction = app(SyncAnnouncements::class);
    $newCount = $syncAction($source);

    expect($newCount)->toBe(2);
    expect(Announcement::count())->toBe(2);

    $first = Announcement::where('source_key', $source->key)->where('source_id', '100')->first();
    expect($first->title)->toBe('公告一')
        ->and($first->source_name)->toBe('教務處')
        ->and($first->category)->toBe('註冊選課')
        ->and($first->url)->toBe('https://example.com/article/100')
        ->and($first->tags)->toBe(['標籤A', '標籤B'])
        ->and($first->published_at)->not->toBeNull();

    $second = Announcement::where('source_key', $source->key)->where('source_id', '200')->first();
    expect($second->title)->toBe('公告二')
        ->and($second->tags)->toBeNull()
        ->and($second->published_at)->toBeNull();
});

it('does not duplicate announcements on repeated sync', function () {
    $source = jsonApiSourceConfig();

    $apiResponse = Http::response([
        'Adverts' => [
            [
                'AdvertID' => 100,
                'Title' => '公告一',
                'Tags' => null,
                'Url' => '/article/100',
                'File' => null,
                'StartDateTime' => '2026-01-01 10:00:00',
            ],
        ],
    ]);

    Http::fake(['example.com/*' => $apiResponse]);

    $syncAction = app(SyncAnnouncements::class);

    $firstCount = $syncAction($source);
    expect($firstCount)->toBe(1);

    $secondCount = $syncAction($source);
    expect($secondCount)->toBe(0);

    expect(Announcement::count())->toBe(1);
});

it('marks announcements as expired when they disappear from JSON API', function () {
    $source = jsonApiSourceConfig();

    Announcement::factory()->create([
        'source_key' => $source->key,
        'source_name' => $source->name,
        'category' => $source->category,
        'source_id' => '100',
        'title' => '即將過期的公告',
    ]);

    Announcement::factory()->create([
        'source_key' => $source->key,
        'source_name' => $source->name,
        'category' => $source->category,
        'source_id' => '200',
        'title' => '仍然存在的公告',
    ]);

    Http::fake([
        'example.com/*' => Http::response([
            'Adverts' => [
                [
                    'AdvertID' => 200,
                    'Title' => '仍然存在的公告',
                    'Tags' => null,
                    'Url' => '/article/200',
                    'File' => null,
                    'StartDateTime' => '2026-01-01 10:00:00',
                ],
            ],
        ]),
    ]);

    $syncAction = app(SyncAnnouncements::class);
    $syncAction($source);

    $expired = Announcement::where('source_key', $source->key)->where('source_id', '100')->first();
    expect($expired->expired_at)->not->toBeNull();

    $active = Announcement::where('source_key', $source->key)->where('source_id', '200')->first();
    expect($active->expired_at)->toBeNull();
});

it('does not track expiry for non-tracking sources', function () {
    $source = jsonApiSourceConfig([
        'tracks_expiry' => false,
    ]);

    Announcement::factory()->create([
        'source_key' => $source->key,
        'source_name' => $source->name,
        'category' => $source->category,
        'source_id' => '100',
        'title' => '不會被過期的公告',
    ]);

    Http::fake([
        'example.com/*' => Http::response([
            'Adverts' => [
                [
                    'AdvertID' => 200,
                    'Title' => '新公告',
                    'Tags' => null,
                    'Url' => '/article/200',
                    'File' => null,
                    'StartDateTime' => '2026-01-01 10:00:00',
                ],
            ],
        ]),
    ]);

    $syncAction = app(SyncAnnouncements::class);
    $syncAction($source);

    $old = Announcement::where('source_key', $source->key)->where('source_id', '100')->first();
    expect($old->expired_at)->toBeNull();
});

it('re-activates previously expired announcements if they reappear', function () {
    $source = jsonApiSourceConfig();

    Announcement::factory()->expired()->create([
        'source_key' => $source->key,
        'source_name' => $source->name,
        'category' => $source->category,
        'source_id' => '100',
        'title' => '重新上架的公告',
    ]);

    Http::fake([
        'example.com/*' => Http::response([
            'Adverts' => [
                [
                    'AdvertID' => 100,
                    'Title' => '重新上架的公告',
                    'Tags' => null,
                    'Url' => '/article/100',
                    'File' => null,
                    'StartDateTime' => '2026-01-01 10:00:00',
                ],
            ],
        ]),
    ]);

    $syncAction = app(SyncAnnouncements::class);
    $syncAction($source);

    $announcement = Announcement::where('source_key', $source->key)->where('source_id', '100')->first();
    expect($announcement->expired_at)->toBeNull();
});

it('updates existing announcement content on repeated sync', function () {
    $source = jsonApiSourceConfig();

    Announcement::factory()->create([
        'source_key' => $source->key,
        'source_name' => $source->name,
        'category' => $source->category,
        'source_id' => '100',
        'title' => '舊標題',
        'url' => 'https://example.com/old',
        'tags' => ['舊標籤'],
    ]);

    Http::fake([
        'example.com/*' => Http::response([
            'Adverts' => [
                [
                    'AdvertID' => 100,
                    'Title' => '新標題',
                    'Tags' => '新標籤',
                    'Url' => '/article/100',
                    'File' => null,
                    'StartDateTime' => '2026-01-01 10:00:00',
                ],
            ],
        ]),
    ]);

    $syncAction = app(SyncAnnouncements::class);
    $syncAction($source);

    $announcement = Announcement::where('source_key', $source->key)->where('source_id', '100')->first();
    expect($announcement->title)->toBe('新標題')
        ->and($announcement->url)->toBe('https://example.com/article/100')
        ->and($announcement->tags)->toBe(['新標籤']);
});
