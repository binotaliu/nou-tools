<?php

use App\Models\Announcement;
use Illuminate\Support\Facades\Http;
use NouTools\Domains\Announcements\Actions\SyncAnnouncements;
use NouTools\Domains\Announcements\DataTransferObjects\AnnouncementSourceConfigDTO;
use NouTools\Domains\Announcements\Fetchers\HtmlNewsBoxFetcher;

function htmlNewsBoxSourceConfig(array $overrides = []): AnnouncementSourceConfigDTO
{
    return AnnouncementSourceConfigDTO::fromConfig(
        $overrides['key'] ?? 'html-news-box-source',
        array_merge([
            'name' => '高雄中心',
            'category' => '最新消息',
            'fetch_url' => 'https://kaohsiung.nou.edu.tw/news_idx.aspx',
            'fetcher_type' => 'html_news_box',
            'fetcher_config' => ['base_url' => 'https://kaohsiung.nou.edu.tw'],
            'tracks_expiry' => false,
            'is_active' => true,
        ], $overrides),
    );
}

it('parses clnews rows into fetched DTOs', function () {
    $source = htmlNewsBoxSourceConfig();

    $html = <<<'HTML'
    <html><body>
    <div class="cl_news_box clearfix">
        <div class="cl_news_list_box clearfix">
            <div class="clnews_rows">
                <div class="cl_news_tag clearfix">
                    <p class="text">重要訊息</p>
                </div>
                <p class="text text-14">
                    <span>2026/04/15</span>
                    <br>
                    <a href="/news_cont.aspx?id=lQ41SYhQ750="><span class="w3-tag w3-teal">Top</span><span class="w3-tag w3-blue">New</span>本校推廣教育處115年6-8月通識教育學分班</a>
                </p>
            </div>
            <div class="clnews_rows">
                <div class="cl_news_tag clearfix">
                    <p class="text">考試資訊</p>
                </div>
                <p class="text text-14">
                    <span>2026/04/13</span>
                    <br>
                    <a href="/news_cont.aspx?id=899LsvoeZ7I="><span class="w3-tag w3-teal">Top</span>114下期中考高雄及屏東考場資訊</a>
                </p>
            </div>
        </div>
    </div>
    </body></html>
    HTML;

    Http::fake([
        'kaohsiung.nou.edu.tw/*' => Http::response($html),
    ]);

    $fetcher = new HtmlNewsBoxFetcher;
    $results = $fetcher->fetch($source);

    expect($results)->toHaveCount(2)
        ->and($results[0]->sourceId)->toBe('/news_cont.aspx?id=lQ41SYhQ750=')
        ->and($results[0]->title)->toBe('本校推廣教育處115年6-8月通識教育學分班')
        ->and($results[0]->url)->toBe('https://kaohsiung.nou.edu.tw/news_cont.aspx?id=lQ41SYhQ750=')
        ->and($results[0]->tags)->toBe(['重要訊息'])
        ->and($results[0]->publishedAt?->format('Y-m-d'))->toBe('2026-04-15')
        ->and($results[1]->title)->toBe('114下期中考高雄及屏東考場資訊')
        ->and($results[1]->tags)->toBe(['考試資訊']);
});

it('syncs clnews rows announcements with category tag', function () {
    $source = htmlNewsBoxSourceConfig();

    $html = <<<'HTML'
    <html><body>
    <div class="cl_news_box clearfix">
        <div class="cl_news_list_box clearfix">
            <div class="clnews_rows">
                <div class="cl_news_tag clearfix">
                    <p class="text">重要訊息</p>
                </div>
                <p class="text text-14">
                    <span>2026/04/15</span>
                    <br>
                    <a href="/news_cont.aspx?id=lQ41SYhQ750="><span class="w3-tag w3-teal">Top</span><span class="w3-tag w3-blue">New</span>公告標題</a>
                </p>
            </div>
        </div>
    </div>
    </body></html>
    HTML;

    Http::fake([
        'kaohsiung.nou.edu.tw/*' => Http::response($html),
    ]);

    $syncAction = app(SyncAnnouncements::class);
    $newCount = $syncAction($source);

    expect($newCount)->toBe(1);

    $announcement = Announcement::first();
    expect($announcement)->not->toBeNull()
        ->and($announcement->title)->toBe('公告標題')
        ->and($announcement->url)->toBe('https://kaohsiung.nou.edu.tw/news_cont.aspx?id=lQ41SYhQ750=')
        ->and($announcement->tags)->toBe(['重要訊息']);
});

it('skips clnews rows without usable link or title', function () {
    $source = htmlNewsBoxSourceConfig([
        'fetch_url' => 'https://example.com/news_idx.aspx',
        'fetcher_config' => ['base_url' => 'https://example.com'],
    ]);

    $html = <<<'HTML'
    <html><body>
    <div class="cl_news_box clearfix">
        <div class="cl_news_list_box clearfix">
            <div class="clnews_rows">
                <p class="text text-14"><a href="">沒有連結</a></p>
            </div>
            <div class="clnews_rows">
                <p class="text text-14"><a href="/news_cont.aspx?id=1"><span class="w3-tag w3-teal">Top</span></a></p>
            </div>
            <div class="clnews_rows">
                <div class="cl_news_tag clearfix">
                    <p class="text">考試資訊</p>
                </div>
                <p class="text text-14">
                    <span>2026/04/13</span>
                    <a href="/news_cont.aspx?id=2"><span class="w3-tag w3-red">Hot</span>有效公告</a>
                </p>
            </div>
        </div>
    </div>
    </body></html>
    HTML;

    Http::fake([
        'example.com/*' => Http::response($html),
    ]);

    $fetcher = new HtmlNewsBoxFetcher;
    $results = $fetcher->fetch($source);

    expect($results)->toHaveCount(1)
        ->and($results[0]->title)->toBe('有效公告')
        ->and($results[0]->tags)->toBe(['考試資訊']);
});
