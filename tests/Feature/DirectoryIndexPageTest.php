<?php

use function Pest\Laravel\get;
use function Pest\Laravel\withoutVite;

beforeEach(function () {
    withoutVite();
});

it('shows link groups with entries grouped by 各處室、學系、學習指導中心', function () {
    $response = get(route('directory.index'));

    $response->assertSuccessful();
    $response->assertSee('各處室');
    $response->assertSee('學系');
    $response->assertSee('學習指導中心');
    $response->assertSee('教務處');
    $response->assertSee('人文學系');
    $response->assertSee('基隆中心');
    $response->assertSee('https://www.nou.edu.tw', escape: false);
});

it('shows address, phone and transport info for 學習指導中心 entries', function () {
    $response = get(route('directory.index'));

    $response->assertSuccessful();
    $response->assertSee('202 基隆市中正區北寧路2號（海洋大學海空大樓8樓）');
    $response->assertSee('02-2462-9938');
    $response->assertSee('開啟中心網站');
    $response->assertSee('交通資訊');
});

it('shows a directory entry point on the home page', function () {
    $response = get(route('home'));

    $response->assertSuccessful();
    $response->assertSee(route('directory.index'));
});

it('displays the directory index markdown page', function () {
    $response = get(route('directory.index.md'));

    $response->assertSuccessful();
    $response->assertHeader('Content-Type', 'text/markdown; charset=utf-8');
    $response->assertSee('# 連結 / 學習指導中心目錄', false);
    $response->assertSee('教務處');
    $response->assertSee('基隆中心');
    $response->assertSee('https://www.nou.edu.tw', escape: false);
    $response->assertSee('https://www.openstreetmap.org/?mlat=', escape: false);
    $response->assertSee('google.com/maps', escape: false);
    $response->assertSee('maps://maps.apple.com/?q=', escape: false);
});

it('returns markdown from the directory index when the client prefers it in the Accept header', function () {
    $response = get(route('directory.index'), [
        'Accept' => 'text/markdown, text/html;q=0.8',
    ]);

    $response->assertSuccessful();
    $response->assertHeader('Content-Type', 'text/markdown; charset=utf-8');
    $response->assertSee('# 連結 / 學習指導中心目錄', false);
});
