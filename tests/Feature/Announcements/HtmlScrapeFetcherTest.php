<?php

use App\Models\Announcement;
use Illuminate\Support\Facades\Http;
use NouTools\Domains\Announcements\Actions\SyncAnnouncements;
use NouTools\Domains\Announcements\DataTransferObjects\AnnouncementSourceConfigDTO;
use NouTools\Domains\Announcements\Fetchers\HtmlScrapeFetcher;

function htmlSourceConfig(array $overrides = []): AnnouncementSourceConfigDTO
{
    return AnnouncementSourceConfigDTO::fromConfig(
        $overrides['key'] ?? 'html-source',
        array_merge([
            'name' => '台中中心',
            'category' => '最新消息',
            'fetch_url' => 'https://example.com/list',
            'fetcher_type' => 'html_scrape',
            'fetcher_config' => ['base_url' => 'https://example.com'],
            'tracks_expiry' => false,
            'is_active' => true,
        ], $overrides),
    );
}

it('parses HTML response into fetched DTOs', function () {
    $source = htmlSourceConfig([
        'fetch_url' => 'https://www2.nou.edu.tw/taichung/doclist.aspx?uid=2554&pid=2553',
        'fetcher_config' => ['base_url' => 'https://www2.nou.edu.tw/taichung'],
    ]);

    $html = <<<'HTML'
    <html><body>
    <ul class="page-list-cont">
        <li>
            <div class="page-list-info">
                <a href="docdetail.aspx?uid=2554&pid=2553&docid=63156" title="考場公告">
                    <div class="page-list-date">
                        <span>2026-04-14</span>
                    </div>
                    <div class="page-list-title">
                        彰化員林國小校區114(下)一般生期中考考場公告
                    </div>
                    <p>本次考場...</p>
                </a>
            </div>
        </li>
        <li>
            <div class="page-list-info">
                <a href="docdetail.aspx?uid=2554&pid=2553&docid=63207" title="南投考場公告">
                    <div class="page-list-date">
                        <span>2026-04-17</span>
                    </div>
                    <div class="page-list-title">
                        南投校區114(下)一般生期中考考場公告
                    </div>
                    <p>期中考...</p>
                </a>
            </div>
        </li>
    </ul>
    </body></html>
    HTML;

    Http::fake([
        'www2.nou.edu.tw/*' => Http::response($html),
    ]);

    $fetcher = new HtmlScrapeFetcher;
    $results = $fetcher->fetch($source);

    expect($results)->toHaveCount(2)
        ->and($results[0]->sourceId)->toBe('docdetail.aspx?uid=2554&pid=2553&docid=63156')
        ->and($results[0]->title)->toBe('彰化員林國小校區114(下)一般生期中考考場公告')
        ->and($results[0]->url)->toBe('https://www2.nou.edu.tw/taichung/docdetail.aspx?uid=2554&pid=2553&docid=63156')
        ->and($results[0]->publishedAt->format('Y-m-d'))->toBe('2026-04-14')
        ->and($results[1]->sourceId)->toBe('docdetail.aspx?uid=2554&pid=2553&docid=63207')
        ->and($results[1]->title)->toBe('南投校區114(下)一般生期中考考場公告');
});

it('syncs new announcements from HTML source', function () {
    $source = htmlSourceConfig([
        'fetch_url' => 'https://www2.nou.edu.tw/taichung/doclist.aspx',
        'fetcher_config' => ['base_url' => 'https://www2.nou.edu.tw/taichung'],
    ]);

    $html = <<<'HTML'
    <html><body>
    <ul class="page-list-cont">
        <li>
            <div class="page-list-info">
                <a href="docdetail.aspx?docid=100">
                    <div class="page-list-date"><span>2026-04-01</span></div>
                    <div class="page-list-title">測試公告一</div>
                </a>
            </div>
        </li>
    </ul>
    </body></html>
    HTML;

    Http::fake([
        'www2.nou.edu.tw/*' => Http::response($html),
    ]);

    $syncAction = app(SyncAnnouncements::class);
    $newCount = $syncAction($source);

    expect($newCount)->toBe(1);
    expect(Announcement::count())->toBe(1);

    $announcement = Announcement::first();
    expect($announcement->title)->toBe('測試公告一')
        ->and($announcement->source_key)->toBe($source->key)
        ->and($announcement->source_name)->toBe($source->name)
        ->and($announcement->category)->toBe($source->category)
        ->and($announcement->url)->toBe('https://www2.nou.edu.tw/taichung/docdetail.aspx?docid=100')
        ->and($announcement->tags)->toBeNull()
        ->and($announcement->expired_at)->toBeNull();
});

it('skips items without title or link', function () {
    $source = htmlSourceConfig();

    $html = <<<'HTML'
    <html><body>
    <ul class="page-list-cont">
        <li>
            <div class="page-list-info">
                <a href="">
                    <div class="page-list-title">沒有連結的公告</div>
                </a>
            </div>
        </li>
        <li>
            <div class="page-list-info">
                <a href="docdetail.aspx?docid=1">
                    <div class="page-list-title">    </div>
                </a>
            </div>
        </li>
        <li>
            <div class="page-list-info">
                <a href="docdetail.aspx?docid=2">
                    <div class="page-list-date"><span>2026-04-01</span></div>
                    <div class="page-list-title">有效的公告</div>
                </a>
            </div>
        </li>
    </ul>
    </body></html>
    HTML;

    Http::fake([
        'example.com/*' => Http::response($html),
    ]);

    $fetcher = new HtmlScrapeFetcher;
    $results = $fetcher->fetch($source);

    expect($results)->toHaveCount(1)
        ->and($results[0]->title)->toBe('有效的公告');
});

it('handles empty HTML list gracefully', function () {
    $source = htmlSourceConfig();

    Http::fake([
        'example.com/*' => Http::response('<html><body><ul class="page-list-cont"></ul></body></html>'),
    ]);

    $fetcher = new HtmlScrapeFetcher;
    $results = $fetcher->fetch($source);

    expect($results)->toHaveCount(0);
});
