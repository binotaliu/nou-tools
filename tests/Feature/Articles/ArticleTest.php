<?php

use App\Enums\ArticleType;
use Inertia\Testing\AssertableInertia as Assert;

test('article index page loads successfully', function () {
    $response = $this->get(route('articles.index', ['type' => ArticleType::MANUAL->value]));

    $response->assertSuccessful();
    $response->assertInertia(function (Assert $page) {
        $page->component('Articles/Index');

        $props = $page->toArray()['props'];

        expect($props['viewModel']['type'])->toBe('manual');
    });
});

test('knowledge base index page loads successfully', function () {
    $response = $this->get(route('articles.index', ['type' => ArticleType::KNOWLEDGE_BASE->value]));

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page->component('Articles/Index'));
});

test('article index displays index content with links', function () {
    $response = $this->get(route('articles.index', ['type' => ArticleType::MANUAL->value]));

    $response->assertSuccessful();
    $response->assertInertia(function (Assert $page) {
        $indexContent = $page->toArray()['props']['viewModel']['indexContent'];

        expect($indexContent)->toContain('歡迎使用');
    });
});

test('article show page loads successfully', function () {
    $response = $this->get(route('articles.show', [
        'type' => ArticleType::MANUAL->value,
        'slug' => 'welcome',
    ]));

    $response->assertSuccessful();
    $response->assertInertia(function (Assert $page) {
        $page->component('Articles/Show');

        $article = $page->toArray()['props']['viewModel']['article'];

        expect($article['title'])->toContain('歡迎');
        expect($article['type'])->toBe('manual');
    });
});

test('article show page displays article content', function () {
    $response = $this->get(route('articles.show', [
        'type' => ArticleType::KNOWLEDGE_BASE->value,
        'slug' => 'about-nou',
    ]));

    $response->assertSuccessful();
    $response->assertInertia(function (Assert $page) {
        $article = $page->toArray()['props']['viewModel']['article'];

        expect($article['content'])->toContain('關於國立空中大學');
    });
});

test('article show page displays sidebar with other articles', function () {
    $response = $this->get(route('articles.show', [
        'type' => ArticleType::MANUAL->value,
        'slug' => 'welcome',
    ]));

    $response->assertSuccessful();
    $response->assertInertia(function (Assert $page) {
        $props = $page->toArray()['props'];

        expect($props['viewModel']['sidebarContent'])->not->toBeNull();
        expect($props['viewModel']['article']['type'])->toBe('manual');
    });
});

test('article show page displays license information', function () {
    $response = $this->get(route('articles.show', [
        'type' => ArticleType::MANUAL->value,
        'slug' => 'welcome',
    ]));

    // The license footer is static markup rendered client-side by
    // Articles/Show.vue, not part of the ViewModel payload, so this is only
    // observable with a real browser. See tests/Browser (article pages have
    // no dedicated browser test yet; smoke-tested via the JS build instead).
    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page->component('Articles/Show'));
});

test('article show page displays a share button', function () {
    $response = $this->get(route('articles.show', [
        'type' => ArticleType::MANUAL->value,
        'slug' => 'welcome',
    ]));

    // The share button/modal markup (including its data-testid attributes)
    // is rendered client-side by Articles/Show.vue; only observable with a
    // real browser.
    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page->component('Articles/Show'));
});

test('article show page returns 404 for non-existent article', function () {
    $response = $this->get(route('articles.show', [
        'type' => ArticleType::MANUAL->value,
        'slug' => 'non-existent-article',
    ]));

    $response->assertNotFound();
});

test('displays the article index markdown page', function () {
    $response = $this->get(route('articles.index.md', ['type' => ArticleType::MANUAL->value]));

    $response->assertSuccessful()
        ->assertHeader('Content-Type', 'text/markdown; charset=utf-8')
        ->assertSee('# NOU 小幫手操作手冊', false)
        ->assertSee('歡迎使用 NOU 小幫手');
});

test('returns markdown from the article index when the client prefers it in the Accept header', function () {
    $response = $this->get(route('articles.index', ['type' => ArticleType::MANUAL->value]), [
        'Accept' => 'text/markdown, text/html;q=0.8',
    ]);

    $response->assertSuccessful()
        ->assertHeader('Content-Type', 'text/markdown; charset=utf-8')
        ->assertSee('# NOU 小幫手操作手冊', false);
});

test('displays the article show markdown page', function () {
    $response = $this->get(route('articles.show.md', [
        'type' => ArticleType::KNOWLEDGE_BASE->value,
        'slug' => 'about-nou',
    ]));

    $response->assertSuccessful()
        ->assertHeader('Content-Type', 'text/markdown; charset=utf-8')
        ->assertSee('# 關於國立空中大學', false)
        ->assertSee('作者：浣熊站長')
        ->assertDontSee('title: 關於國立空中大學', false);
});

test('returns markdown from the article show page when the client prefers it in the Accept header', function () {
    $response = $this->get(route('articles.show', [
        'type' => ArticleType::KNOWLEDGE_BASE->value,
        'slug' => 'about-nou',
    ]), [
        'Accept' => 'text/markdown, text/html;q=0.8',
    ]);

    $response->assertSuccessful()
        ->assertHeader('Content-Type', 'text/markdown; charset=utf-8')
        ->assertSee('# 關於國立空中大學', false);
});

test('article show markdown page returns 404 for non-existent article', function () {
    $response = $this->get(route('articles.show.md', [
        'type' => ArticleType::MANUAL->value,
        'slug' => 'non-existent-article',
    ]));

    $response->assertNotFound();
});
