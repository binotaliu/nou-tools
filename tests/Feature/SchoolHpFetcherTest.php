<?php

use App\Models\Announcement;
use Illuminate\Support\Facades\Http;
use NouTools\Domains\Announcements\Actions\SyncAnnouncements;
use NouTools\Domains\Announcements\DataTransferObjects\AnnouncementSourceConfigDTO;
use NouTools\Domains\Announcements\Fetchers\SchoolHpFetcher;

function schoolHpSourceConfig(array $overrides = []): AnnouncementSourceConfigDTO
{
    return AnnouncementSourceConfigDTO::fromConfig(
        $overrides['key'] ?? 'school-homepage-source',
        array_merge([
            'name' => '學校首頁',
            'category' => '最新消息',
            'fetch_url' => 'https://www.nou.edu.tw/',
            'fetcher_type' => 'school_hp',
            'fetcher_config' => ['base_url' => 'https://www.nou.edu.tw'],
            'tracks_expiry' => false,
            'is_active' => true,
        ], $overrides),
    );
}

it('parses tab list items across every news tab', function () {
    $source = schoolHpSourceConfig();

    $html = <<<'HTML'
    <html><body>
    <div id="news-content">
        <ul id="tab-2">
            <li>
                <div>
                    <div>
                        <span class="inline-block text-xs text-white bg-nou-red">置頂</span>
                        <a href="/Home/NewsDetails/9406" title="本校校慶40週年－環台雲端接力GO連結網址">本校校慶40週年－環台雲端接力GO連結網址</a>
                    </div>
                    <span class="text-xs text-gray-500 whitespace-nowrap font-mono">2026/08/10</span>
                </div>
            </li>
            <li>
                <div>
                    <div>
                        <a href="/Home/NewsDetails/9288" title="數位學習平台伺服(uu.nou.edu.tw)異常">數位學習平台伺服(uu.nou.edu.tw)異常</a>
                    </div>
                    <span class="text-xs text-gray-500 whitespace-nowrap font-mono">2025/12/23</span>
                </div>
            </li>
        </ul>

        <ul id="tab-6">
            <li>
                <div>
                    <div>
                        <a href="https://studadm.nou.edu.tw/FileManage/download?categoryId=12" title="國立空中大學教學媒體處行政組員(大學級)徵才公告">國立空中大學教學媒體處行政組員(大學級)徵才公告</a>
                    </div>
                    <span class="text-xs text-gray-500 whitespace-nowrap font-mono">2026/04/27</span>
                </div>
            </li>
        </ul>
    </div>
    </body></html>
    HTML;

    Http::fake([
        'www.nou.edu.tw/*' => Http::response($html),
    ]);

    $fetcher = new SchoolHpFetcher;
    $results = $fetcher->fetch($source);

    expect($results)->toHaveCount(3)
        ->and($results[0]->sourceId)->toBe('/Home/NewsDetails/9406')
        ->and($results[0]->title)->toBe('本校校慶40週年－環台雲端接力GO連結網址')
        ->and($results[0]->url)->toBe('https://www.nou.edu.tw/Home/NewsDetails/9406')
        ->and($results[0]->tags)->toBeNull()
        ->and($results[0]->publishedAt?->format('Y-m-d'))->toBe('2026-08-10')
        ->and($results[1]->title)->toBe('數位學習平台伺服(uu.nou.edu.tw)異常')
        ->and($results[2]->sourceId)->toBe('https://studadm.nou.edu.tw/FileManage/download?categoryId=12')
        ->and($results[2]->url)->toBe('https://studadm.nou.edu.tw/FileManage/download?categoryId=12');
});

it('skips tab items without href, title, or date', function () {
    $source = schoolHpSourceConfig([
        'fetch_url' => 'https://example.com/',
        'fetcher_config' => ['base_url' => 'https://example.com'],
    ]);

    $html = <<<'HTML'
    <html><body>
    <div id="news-content">
        <ul id="tab-1">
            <li>
                <div>
                    <div><a href="" title="沒有連結">沒有連結</a></div>
                    <span class="font-mono">2026/05/02</span>
                </div>
            </li>
            <li>
                <div>
                    <div><a href="/news/1">這筆沒有日期</a></div>
                </div>
            </li>
            <li>
                <div>
                    <div><a href="/news/2"></a></div>
                    <span class="font-mono">2026/05/02</span>
                </div>
            </li>
            <li>
                <div>
                    <div><a href="/news/3" title="有效公告">有效公告</a></div>
                    <span class="font-mono">2026/05/02</span>
                </div>
            </li>
        </ul>
    </div>
    </body></html>
    HTML;

    Http::fake([
        'example.com/*' => Http::response($html),
    ]);

    $fetcher = new SchoolHpFetcher;
    $results = $fetcher->fetch($source);

    expect($results)->toHaveCount(1)
        ->and($results[0]->sourceId)->toBe('/news/3')
        ->and($results[0]->title)->toBe('有效公告')
        ->and($results[0]->url)->toBe('https://example.com/news/3');
});

it('syncs school homepage announcements', function () {
    $source = schoolHpSourceConfig();

    $html = <<<'HTML'
    <html><body>
    <div id="news-content">
        <ul id="tab-2">
            <li>
                <div>
                    <div>
                        <span class="inline-block text-xs text-white bg-nou-red">置頂</span>
                        <a href="/Home/NewsDetails/SYNC" title="115年國立空中大學徵聘特殊教育資源中心輔導人員">115年國立空中大學徵聘特殊教育資源中心輔導人員</a>
                    </div>
                    <span class="text-xs text-gray-500 whitespace-nowrap font-mono">2026/04/28</span>
                </div>
            </li>
        </ul>
    </div>
    </body></html>
    HTML;

    Http::fake([
        'www.nou.edu.tw/*' => Http::response($html),
    ]);

    $syncAction = app(SyncAnnouncements::class);
    $newCount = $syncAction($source);

    expect($newCount)->toBe(1)
        ->and(Announcement::query()->count())->toBe(1);

    $announcement = Announcement::query()->first();
    expect($announcement)->not->toBeNull()
        ->and($announcement->source_key)->toBe($source->key)
        ->and($announcement->source_name)->toBe($source->name)
        ->and($announcement->category)->toBe($source->category)
        ->and($announcement->source_id)->toBe('/Home/NewsDetails/SYNC')
        ->and($announcement->title)->toBe('115年國立空中大學徵聘特殊教育資源中心輔導人員')
        ->and($announcement->url)->toBe('https://www.nou.edu.tw/Home/NewsDetails/SYNC')
        ->and($announcement->tags)->toBeNull()
        ->and($announcement->published_at?->format('Y-m-d'))->toBe('2026-04-28');
});
