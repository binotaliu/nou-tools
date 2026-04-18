<?php

use App\Models\Announcement;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

it('fetches announcements from all active sources', function () {
    Config::set('announcements.sources', [
        'active-source' => [
            'name' => '測試來源',
            'category' => '最新消息',
            'fetch_url' => 'https://example.com/api',
            'fetcher_type' => 'json_api',
            'fetcher_config' => ['base_url' => 'https://example.com'],
            'tracks_expiry' => true,
            'is_active' => true,
        ],
        'inactive-source' => [
            'name' => '停用來源',
            'category' => '最新消息',
            'fetch_url' => 'https://inactive.com/api',
            'fetcher_type' => 'json_api',
            'fetcher_config' => ['base_url' => 'https://inactive.com'],
            'tracks_expiry' => false,
            'is_active' => false,
        ],
    ]);

    Http::fake([
        'example.com/*' => Http::response([
            'Adverts' => [
                [
                    'AdvertID' => 1,
                    'Title' => '測試公告',
                    'Tags' => null,
                    'Url' => '/article/1',
                    'File' => null,
                    'StartDateTime' => '2026-01-01 10:00:00',
                ],
            ],
        ]),
        'inactive.com/*' => Http::response(['Adverts' => []]),
    ]);

    $this->artisan('announcements:fetch')
        ->assertSuccessful();

    expect(Announcement::count())->toBe(1);
    Http::assertSentCount(1);
});

it('fetches only a specific source with --source option', function () {
    Config::set('announcements.sources', [
        'source-1' => [
            'name' => '來源一',
            'category' => '公告',
            'fetch_url' => 'https://source1.com/api',
            'fetcher_type' => 'json_api',
            'fetcher_config' => ['base_url' => 'https://source1.com'],
            'tracks_expiry' => true,
            'is_active' => true,
        ],
        'source-2' => [
            'name' => '來源二',
            'category' => '公告',
            'fetch_url' => 'https://source2.com/api',
            'fetcher_type' => 'json_api',
            'fetcher_config' => ['base_url' => 'https://source2.com'],
            'tracks_expiry' => true,
            'is_active' => true,
        ],
    ]);

    Http::fake([
        'source1.com/*' => Http::response([
            'Adverts' => [
                ['AdvertID' => 1, 'Title' => '來源一公告', 'Tags' => null, 'Url' => '/1', 'File' => null, 'StartDateTime' => null],
            ],
        ]),
        'source2.com/*' => Http::response([
            'Adverts' => [
                ['AdvertID' => 2, 'Title' => '來源二公告', 'Tags' => null, 'Url' => '/2', 'File' => null, 'StartDateTime' => null],
            ],
        ]),
    ]);

    $this->artisan('announcements:fetch', ['--source' => 'source-1'])
        ->assertSuccessful();

    expect(Announcement::count())->toBe(1);
    expect(Announcement::first()->title)->toBe('來源一公告');
});

it('handles fetch errors gracefully', function () {
    Config::set('announcements.sources', [
        'broken-source' => [
            'name' => '故障來源',
            'category' => '公告',
            'fetch_url' => 'https://broken.com/api',
            'fetcher_type' => 'json_api',
            'fetcher_config' => ['base_url' => 'https://broken.com'],
            'tracks_expiry' => true,
            'is_active' => true,
        ],
    ]);

    Http::fake([
        'broken.com/*' => Http::response('Internal Server Error', 500),
    ]);

    $this->artisan('announcements:fetch')
        ->assertSuccessful()
        ->expectsOutputToContain('擷取失敗');
});
