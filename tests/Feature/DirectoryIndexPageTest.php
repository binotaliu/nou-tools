<?php

use function Pest\Laravel\get;

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
