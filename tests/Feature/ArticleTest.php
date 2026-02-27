<?php

use App\Data\Article;
use App\Enums\ArticleType;
use App\Services\ArticleService;
use Carbon\Carbon;

beforeEach(function () {
    // swap the real service for a mock so views never hit the filesystem
    $this->articleService = Mockery::mock(ArticleService::class);
    $this->app->instance(ArticleService::class, $this->articleService);
});

// helper for a generic article object
function sampleArticle(string $slug, ArticleType $type): Article
{
    return new Article(
        slug: $slug,
        type: $type,
        title: 'Test title',
        author: 'NOU 小幫手團隊',
        publishedAt: Carbon::now(),
        content: '<p>Some content</p>',
        description: ''
    );
}

test('article index page loads successfully', function () {
    $this->articleService->shouldReceive('getIndex')
        ->once()
        ->with(ArticleType::MANUAL)
        ->andReturn('<h1>操作手冊</h1>');

    $response = $this->get(route('articles.index', ['type' => ArticleType::MANUAL->value]));

    $response->assertStatus(200)
        ->assertSee('操作手冊');
});

test('knowledge base index page loads successfully', function () {
    $this->articleService->shouldReceive('getIndex')
        ->once()
        ->with(ArticleType::KNOWLEDGE_BASE)
        ->andReturn('<h1>知識庫</h1>');

    $response = $this->get(route('articles.index', ['type' => ArticleType::KNOWLEDGE_BASE->value]));

    $response->assertStatus(200)
        ->assertSee('知識庫');
});

test('article index displays index content with links', function () {
    $this->articleService->shouldReceive('getIndex')
        ->once()
        ->with(ArticleType::MANUAL)
        ->andReturn('<h1>操作手冊</h1><p>歡迎使用 <a href="#">NOU 小幫手</a></p>');

    $response = $this->get(route('articles.index', ['type' => ArticleType::MANUAL->value]));

    $response->assertStatus(200)
        // assertSeeText treats HTML tags as plain text
        ->assertSeeText('歡迎使用 NOU 小幫手');
});

test('article show page loads successfully', function () {
    $article = sampleArticle('welcome', ArticleType::MANUAL);

    $this->articleService->shouldReceive('getArticle')
        ->once()
        ->with(ArticleType::MANUAL, 'welcome')
        ->andReturn($article);
    $this->articleService->shouldReceive('getSidebar')
        ->once()
        ->with(ArticleType::MANUAL)
        ->andReturn('<div>操作手冊</div>');

    $response = $this->get(route('articles.show', [
        'type' => ArticleType::MANUAL->value,
        'slug' => 'welcome',
    ]));

    $response->assertStatus(200)
        ->assertSee('Some content')
        ->assertSee('NOU 小幫手團隊')
        ->assertSee('操作手冊');
});

test('article show page displays article content', function () {
    $article = new Article(
        slug: 'about-nou',
        type: ArticleType::KNOWLEDGE_BASE,
        title: 'Test title',
        author: 'NOU 小幫手團隊',
        publishedAt: Carbon::now(),
        content: '<p>關於國立空中大學</p>',
        description: ''
    );

    $this->articleService->shouldReceive('getArticle')
        ->once()
        ->with(ArticleType::KNOWLEDGE_BASE, 'about-nou')
        ->andReturn($article);
    $this->articleService->shouldReceive('getSidebar')
        ->once()
        ->with(ArticleType::KNOWLEDGE_BASE)
        ->andReturn('<div></div>');

    $response = $this->get(route('articles.show', [
        'type' => ArticleType::KNOWLEDGE_BASE->value,
        'slug' => 'about-nou',
    ]));

    $response->assertStatus(200)
        ->assertSee('關於國立空中大學');
});

test('article show page displays sidebar with other articles', function () {
    $article = sampleArticle('welcome', ArticleType::MANUAL);

    $this->articleService->shouldReceive('getArticle')
        ->andReturn($article);
    $this->articleService->shouldReceive('getSidebar')
        ->andReturn('<a href="#">返回列表</a>');

    $response = $this->get(route('articles.show', [
        'type' => ArticleType::MANUAL->value,
        'slug' => 'welcome',
    ]));

    $response->assertStatus(200)
        ->assertSee('操作手冊')
        ->assertSee('返回列表');
});

test('article show page displays license information', function () {
    $article = sampleArticle('welcome', ArticleType::MANUAL);

    $this->articleService->shouldReceive('getArticle')
        ->andReturn($article);
    $this->articleService->shouldReceive('getSidebar')
        ->andReturn('<div></div>');

    $response = $this->get(route('articles.show', [
        'type' => ArticleType::MANUAL->value,
        'slug' => 'welcome',
    ]));

    $response->assertStatus(200)
        ->assertSee('授權方式')
        ->assertSee('CC BY-NC-SA 4.0');
});

test('article show page returns 404 for non-existent article', function () {
    $this->articleService->shouldReceive('getArticle')
        ->once()
        ->with(ArticleType::MANUAL, 'non-existent-article')
        ->andReturn(null);

    $response = $this->get(route('articles.show', [
        'type' => ArticleType::MANUAL->value,
        'slug' => 'non-existent-article',
    ]));

    $response->assertStatus(404);
});
