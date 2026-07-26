<?php

use App\Enums\ArticleType;

test('article index page loads successfully', function () {
    $response = $this->get(route('articles.index', ['type' => ArticleType::MANUAL->value]));

    $response->assertSuccessful()
        ->assertSee('操作手冊');
});

test('knowledge base index page loads successfully', function () {
    $response = $this->get(route('articles.index', ['type' => ArticleType::KNOWLEDGE_BASE->value]));

    $response->assertSuccessful();
});

test('article index displays index content with links', function () {
    $response = $this->get(route('articles.index', ['type' => ArticleType::MANUAL->value]));

    $response->assertSuccessful()
        ->assertSeeText('歡迎使用');
});

test('article show page loads successfully', function () {
    $response = $this->get(route('articles.show', [
        'type' => ArticleType::MANUAL->value,
        'slug' => 'welcome',
    ]));

    $response->assertSuccessful()
        ->assertSee('歡迎')
        ->assertSee('操作手冊');
});

test('article show page displays article content', function () {
    $response = $this->get(route('articles.show', [
        'type' => ArticleType::KNOWLEDGE_BASE->value,
        'slug' => 'about-nou',
    ]));

    $response->assertSuccessful()
        ->assertSee('關於國立空中大學');
});

test('article show page displays sidebar with other articles', function () {
    $response = $this->get(route('articles.show', [
        'type' => ArticleType::MANUAL->value,
        'slug' => 'welcome',
    ]));

    $response->assertSuccessful()->assertSee('操作手冊');
});

test('article show page displays license information', function () {
    $response = $this->get(route('articles.show', [
        'type' => ArticleType::MANUAL->value,
        'slug' => 'welcome',
    ]));

    $response->assertSuccessful()
        ->assertSee('授權方式')
        ->assertSee('CC BY-NC-SA 4.0');
});

test('article show page displays a share button', function () {
    $response = $this->get(route('articles.show', [
        'type' => ArticleType::MANUAL->value,
        'slug' => 'welcome',
    ]));

    $response->assertSuccessful()
        ->assertSee('data-testid="article-share-button"', false)
        ->assertSee('data-testid="article-share-modal"', false);
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
