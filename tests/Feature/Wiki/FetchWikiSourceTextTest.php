<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use NouTools\Domains\Wiki\Exceptions\WikiSourceFetchException;
use NouTools\Domains\Wiki\Support\FetchWikiSourceText;

beforeEach(function (): void {
    config(['wiki.source_domains' => ['nou.edu.tw']]);
});

it('turns an html page into plain text as a single page', function () {
    Http::fake(['www.nou.edu.tw/a' => Http::response('<html><nav>menu</nav><p>第一段</p><script>x()</script><p>第二段</p></html>', 200, ['Content-Type' => 'text/html'])]);

    $source = app(FetchWikiSourceText::class)('https://www.nou.edu.tw/a', 1_000_000);

    expect($source->url)->toBe('https://www.nou.edu.tw/a')
        ->and($source->text)->toBe("第一段\n第二段")
        ->and($source->pages)->toBe(["第一段\n第二段"]);
});

it('follows redirects within allowed hosts and reports the final url', function () {
    Http::fake([
        'www.nou.edu.tw/old' => Http::response('', 302, ['Location' => '/new']),
        'www.nou.edu.tw/new' => Http::response('<p>內容</p>', 200, ['Content-Type' => 'text/html']),
    ]);

    expect(app(FetchWikiSourceText::class)('https://www.nou.edu.tw/old', 1_000_000)->url)
        ->toBe('https://www.nou.edu.tw/new');
});

it('refuses a redirect to a host that is not allowed', function () {
    Http::fake(['www.nou.edu.tw/a' => Http::response('', 302, ['Location' => 'https://evil.example.com/x'])]);

    app(FetchWikiSourceText::class)('https://www.nou.edu.tw/a', 1_000_000);
})->throws(WikiSourceFetchException::class, 'redirects to a host that is not allowed');

it('refuses a file over the download cap', function () {
    Http::fake(['www.nou.edu.tw/a' => Http::response(str_repeat('x', 100), 200, ['Content-Type' => 'text/html'])]);

    app(FetchWikiSourceText::class)('https://www.nou.edu.tw/a', 50);
})->throws(WikiSourceFetchException::class, 'too large');

it('reports a failing status', function () {
    Http::fake(['www.nou.edu.tw/a' => Http::response('', 404)]);

    app(FetchWikiSourceText::class)('https://www.nou.edu.tw/a', 1_000_000);
})->throws(WikiSourceFetchException::class, 'HTTP 404');
