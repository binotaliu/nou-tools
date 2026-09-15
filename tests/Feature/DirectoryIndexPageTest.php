<?php

use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\get;
use function Pest\Laravel\withoutVite;

beforeEach(function () {
    withoutVite();
});

it('shows link groups with entries grouped by 各處室、學系、學習指導中心', function () {
    $response = get(route('directory.index'));

    $response->assertSuccessful();
    $response->assertInertia(function (Assert $page) {
        $page->component('Directory/Index');

        $props = $page->toArray()['props'];
        $labels = collect($props['viewModel']['linkGroups'])->pluck('label');

        expect($labels)->toContain('各處室', '學系');
        expect($props['viewModel']['centerGroup']['label'] ?? null)->toBe('學習指導中心');

        $linkNames = collect($props['viewModel']['linkGroups'])
            ->flatMap(fn (array $group) => collect($group['links'])->pluck('name'));
        $linkUrls = collect($props['viewModel']['linkGroups'])
            ->flatMap(fn (array $group) => collect($group['links'])->pluck('url'));

        expect($linkNames)->toContain('教務處');
        expect(collect($props['viewModel']['centerGroup']['centers'] ?? [])->pluck('name'))->toContain('基隆中心');
        expect($linkUrls->contains(fn (string $url) => str_contains($url, 'https://www.nou.edu.tw')))->toBeTrue();
    });
});

it('shows address, phone and transport info for 學習指導中心 entries', function () {
    $response = get(route('directory.index'));

    $response->assertSuccessful();
    $response->assertInertia(function (Assert $page) {
        $props = $page->toArray()['props'];
        $centers = collect($props['viewModel']['centerGroup']['centers'] ?? []);

        expect($centers->pluck('address'))->toContain('202 基隆市中正區北寧路2號（海洋大學海空大樓8樓）');
        expect(
            $centers->flatMap(fn (array $center) => collect($center['phone'])->pluck('display'))
        )->toContain('02-2462-9938');
        expect($centers->pluck('transportUrl')->filter())->not->toBeEmpty();
    });
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
