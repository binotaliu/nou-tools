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

test('article show page returns 404 for non-existent article', function () {
    $response = $this->get(route('articles.show', [
        'type' => ArticleType::MANUAL->value,
        'slug' => 'non-existent-article',
    ]));

    $response->assertNotFound();
});
