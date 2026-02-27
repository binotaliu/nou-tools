<?php

use App\Data\Article;
use App\Enums\ArticleType;
use App\Services\ArticleService;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    // always instantiate a fresh service
    $this->articleService = new ArticleService;

    // helpers stored on the test instance to avoid polluting global namespace
    $this->articlePath = fn (\App\Enums\ArticleType $type, string $slug): string => resource_path("articles/{$type->directory()}/{$slug}.md");

    $this->sampleMarkdown = function (): string {
        return <<<'MD'
---
title: My Test Article
author: Test Author
published_at: 2020-01-01
---

# Heading

Content paragraph.
MD;
    };

    // reset any previous facade expectations

});

test('can get an article from markdown file', function () {
    // arrange: fake the file facade
    $type = ArticleType::MANUAL;
    $slug = 'welcome';
    $path = ($this->articlePath)($type, $slug);

    File::shouldReceive('exists')->once()->with($path)->andReturn(true);
    File::shouldReceive('get')->once()->with($path)->andReturn(($this->sampleMarkdown)());

    // act
    $article = $this->articleService->getArticle($type, $slug);

    // assert
    expect($article)
        ->toBeInstanceOf(Article::class)
        ->and($article->title)->toBe('My Test Article')
        ->and($article->author)->toBe('Test Author')
        ->and($article->type)->toBe($type)
        ->and($article->slug)->toBe($slug)
        ->and($article->content)->toContain('<h1>Heading</h1>');
});

test('returns null for non-existent article', function () {
    $type = ArticleType::MANUAL;
    $slug = 'non-existent';
    $path = ($this->articlePath)($type, $slug);

    File::shouldReceive('exists')->once()->with($path)->andReturn(false);

    $article = $this->articleService->getArticle($type, $slug);

    expect($article)->toBeNull();
});

test('article content is converted from markdown to html', function () {
    $type = ArticleType::KNOWLEDGE_BASE;
    $slug = 'about-nou';
    $path = ($this->articlePath)($type, $slug);

    // add minimal frontmatter so the service returns an Article instance
    $markdown = <<<'MD'
---
title: Foo
author: Bar
---

# Title

Some **markdown** text.
MD;

    File::shouldReceive('exists')->once()->with($path)->andReturn(true);
    File::shouldReceive('get')->once()->with($path)->andReturn($markdown);

    $article = $this->articleService->getArticle($type, $slug);

    expect($article->content)
        ->toContain('<h1>')
        ->toContain('</h1>')
        ->toContain('<p>')
        ->toContain('</p>');
});

test('can get index content', function () {
    $type = ArticleType::MANUAL;
    $path = resource_path("articles/{$type->directory()}/_index.md");
    $markdown = "# 操作手冊\n\n歡迎使用 NOU 小幫手";

    File::shouldReceive('exists')->once()->with($path)->andReturn(true);
    File::shouldReceive('get')->once()->with($path)->andReturn($markdown);

    $indexContent = $this->articleService->getIndex($type);

    expect($indexContent)
        ->toBeString()
        ->toContain('<h1>操作手冊</h1>')
        ->toContain('歡迎使用 NOU 小幫手');
});

test('returns null when index does not exist', function () {
    $type = ArticleType::MANUAL;
    $path = resource_path("articles/{$type->directory()}/_index.md");

    File::shouldReceive('exists')->once()->with($path)->andReturn(false);

    $indexContent = $this->articleService->getIndex($type);

    expect($indexContent)->toBeNull();
});

test('can get sidebar content', function () {
    $type = ArticleType::MANUAL;
    $path = resource_path("articles/{$type->directory()}/_sidebar.md");
    $markdown = "## 文章列表\n\n歡迎使用 NOU 小幫手";

    File::shouldReceive('exists')->once()->with($path)->andReturn(true);
    File::shouldReceive('get')->once()->with($path)->andReturn($markdown);

    $sidebarContent = $this->articleService->getSidebar($type);

    expect($sidebarContent)
        ->toBeString()
        ->toContain('<h2>文章列表</h2>')
        ->toContain('歡迎使用 NOU 小幫手');
});

test('returns null when sidebar does not exist', function () {
    $type = ArticleType::MANUAL;
    $path = resource_path("articles/{$type->directory()}/_sidebar.md");

    File::shouldReceive('exists')->once()->with($path)->andReturn(false);

    $sidebar = $this->articleService->getSidebar($type);

    expect($sidebar)->toBeNull();
});

// security: slugs containing forbidden characters should be treated as
// missing articles rather than being used to construct a path.
test('invalid slug is ignored and returns null', function () {
    $type = ArticleType::MANUAL;
    $slug = '../secrets';

    // service should reject the slug before touching the filesystem
    File::shouldReceive('exists')->never();
    File::shouldReceive('get')->never();

    $article = $this->articleService->getArticle($type, $slug);
    expect($article)->toBeNull();
});

test('slug with directory separator returns null', function () {
    $type = ArticleType::MANUAL;
    $slug = 'foo/bar';

    File::shouldReceive('exists')->never();
    File::shouldReceive('get')->never();

    $article = $this->articleService->getArticle($type, $slug);
    expect($article)->toBeNull();
});
