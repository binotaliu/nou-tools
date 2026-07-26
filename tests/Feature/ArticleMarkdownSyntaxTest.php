<?php

use App\Enums\ArticleType;
use Illuminate\Support\Facades\File;
use League\CommonMark\Extension\FrontMatter\Data\SymfonyYamlFrontMatterParser;
use League\CommonMark\Extension\FrontMatter\FrontMatterParser;
use NouTools\Domains\Articles\Markdown\ArticleMarkdownConverterFactory;

beforeEach(function () {
    $this->convert = fn (string $markdown): string => (new ArticleMarkdownConverterFactory)->make()->convert($markdown)->getContent();
});

// --- 2.1 dialogue -----------------------------------------------------------

test('dialogue renders turns with persona, side, and mood', function () {
    $html = ($this->convert)(<<<'MD'
:::dialogue
新生：我剛註冊了空大，接下來要怎麼辦？

浣熊站長：很多新同學都會有這個困擾！讓我們一起來看看接下來要做什麼。

    註冊完成後，第一件事是確認自己的**選課清單**。

新生（緊張）：那我需要自己去買書嗎？
:::
MD);

    expect($html)
        ->toContain('<div class="md-dialogue" role="group" aria-label="對話">')
        ->toContain('data-side="start"')
        ->toContain('data-persona="student"')
        ->toContain('data-persona="raccoon"')
        ->toContain('data-side="end"')
        ->toContain('<p class="md-dialogue-speaker">新生</p>')
        ->toContain('<p class="md-dialogue-speaker">浣熊站長</p>')
        ->toContain('data-mood="緊張"')
        ->toContain('<div class="md-dialogue-body"><p>我剛註冊了空大，接下來要怎麼辦？</p></div>')
        ->toContain('<p>註冊完成後，第一件事是確認自己的<strong>選課清單</strong>。</p>')
        ->not->toContain('<pre>')
        ->toContain('🙋')
        ->toContain('🦝');
});

test('an indented continuation paragraph inside a dialogue turn stays a normal paragraph', function () {
    $html = ($this->convert)(<<<'MD'
:::dialogue
浣熊站長：說明一下。

    這是縮排的延續段落，不應該變成程式碼區塊。

新生：了解。
:::
MD);

    expect($html)
        ->toContain('<p>這是縮排的延續段落，不應該變成程式碼區塊。</p>')
        ->not->toContain('<pre>')
        ->not->toContain('<code>');
});

test('a fenced code block still renders as code inside a dialogue turn', function () {
    $html = ($this->convert)(<<<'MD'
:::dialogue
浣熊站長：來看範例程式碼。

```
echo 1;
```
:::
MD);

    expect($html)
        ->toContain('<pre><code>echo 1;')
        ->toContain('</code></pre>');
});

test('dialogue falls back to a neutral persona for an unknown speaker', function () {
    $html = ($this->convert)(<<<'MD'
:::dialogue
教務長：這是誰？
:::
MD);

    expect($html)
        ->toContain('data-persona="neutral"')
        ->toContain('data-side="start"')
        ->toContain('<p class="md-dialogue-speaker">教務長</p>')
        ->toContain('>教<');
});

test('dialogue escapes speaker names and does not execute embedded html', function () {
    $html = ($this->convert)(<<<'MD'
:::dialogue
新生<b>x</b>：測試
:::
MD);

    expect($html)
        ->toContain('<p class="md-dialogue-speaker">新生&lt;b&gt;x&lt;/b&gt;</p>')
        ->not->toContain('<b>x</b>');
});

test('content before the first speaker paragraph renders as plain narration', function () {
    $html = ($this->convert)(<<<'MD'
:::dialogue
這是開場白，還沒有人說話。

新生：現在才是第一句對話。
:::
MD);

    expect($html)
        ->toContain('<div class="md-dialogue" role="group" aria-label="對話"><p>這是開場白，還沒有人說話。</p>')
        ->toContain('<p class="md-dialogue-speaker">新生</p>');
});

// --- 2.2 callouts ------------------------------------------------------------

test('github alert syntax renders as a callout with the default label and icon', function () {
    $html = ($this->convert)(<<<'MD'
> [!TIP] 買書小撇步
> 空大的教科書可以在校內書局、線上書城或二手社團購買。
MD);

    expect($html)
        ->toContain('<div class="md-callout" data-type="tip">')
        ->toContain('<svg class="md-callout-icon"')
        ->toContain('aria-hidden="true"')
        ->toContain('</svg>買書小撇步')
        ->toContain('<div class="md-callout-body"><p>空大的教科書可以在校內書局、線上書城或二手社團購買。</p></div>');
});

test('github alert syntax falls back to the default label when no title is given', function () {
    $html = ($this->convert)(<<<'MD'
> [!MONEY]
> 費用相關內容。
MD);

    expect($html)
        ->toContain('data-type="money"')
        ->toContain('class="md-callout-icon"')
        ->toContain('</svg>費用');
});

test('an unrecognised alert type is left as a normal blockquote', function () {
    $html = ($this->convert)(<<<'MD'
> [!BOGUS] not a real type
> content
MD);

    expect($html)
        ->not->toContain('md-callout')
        ->toContain('<blockquote>');
});

test('container alias form renders the same callout with a custom title', function () {
    $html = ($this->convert)(":::exam 期中考範圍\n考試會考前六章。\n:::\n");

    expect($html)
        ->toContain('<div class="md-callout" data-type="exam">')
        ->toContain('</svg>期中考範圍')
        ->toContain('<div class="md-callout-body"><p>考試會考前六章。</p></div>');
});

test('callout container title is escaped', function () {
    $html = ($this->convert)(":::note <b>xss</b>\n內容\n:::\n");

    expect($html)
        ->toContain('&lt;b&gt;xss&lt;/b&gt;')
        ->not->toContain('<b>xss</b>');
});

test('an unknown icon name degrades to a title-only callout', function () {
    config()->set('markdown.callouts.note', ['label' => '補充說明', 'icon' => 'heroicon-o-not-a-real-icon']);

    $html = ($this->convert)(":::note\n內容\n:::\n");

    expect($html)
        ->toContain('<p class="md-callout-title">補充說明</p>')
        ->not->toContain('<svg');
});

test('every configured callout icon resolves to a heroicon', function () {
    foreach (array_keys(config('markdown.callouts')) as $type) {
        expect(($this->convert)(":::{$type}\n內容\n:::\n"))
            ->toContain('class="md-callout-icon"');
    }
});

// --- 2.3 steps ---------------------------------------------------------------

test('steps wraps an ordered list with numbered markers and a title', function () {
    $html = ($this->convert)(<<<'MD'
:::steps 註冊後的四個步驟
1. 收到學生證與課程資料
2. 上網完成選課確認
3. 購買或借閱教科書
4. 登入數位學習平台
:::
MD);

    expect($html)
        ->toContain('<div class="md-steps-wrap"><p class="md-steps-title">註冊後的四個步驟</p>')
        ->toContain('<ol class="md-steps" data-title="註冊後的四個步驟">')
        ->toContain('<div class="md-step-marker" aria-hidden="true">1</div>')
        ->toContain('<div class="md-step-marker" aria-hidden="true">4</div>');
});

// --- 2.4 faq -------------------------------------------------------------

test('faq turns headings into collapsible details', function () {
    $html = ($this->convert)(<<<'MD'
:::faq
### 沒有高中學歷可以讀空大嗎？
可以，年滿 18 歲即可就讀選修生。

### 一定要到校上課嗎？
不一定。
:::
MD);

    expect($html)
        ->toContain('<div class="md-faq">')
        ->toContain('<summary class="md-faq-question">沒有高中學歷可以讀空大嗎？</summary>')
        ->toContain('<div class="md-faq-answer"><p>可以，年滿 18 歲即可就讀選修生。</p></div>')
        ->not->toContain('open');
});

test('faq opens the first item when the container argument is "open"', function () {
    $html = ($this->convert)(<<<'MD'
:::faq open
### 問題一
答案一
:::
MD);

    expect($html)->toContain('<details class="md-faq-item" open>');
});

// --- 2.5 tabs ------------------------------------------------------------

test('tabs render alpine-powered panels with escaped titles', function () {
    $html = ($this->convert)(<<<'MD'
::::tabs
:::tab iPhone / iPad
內容一
:::
:::tab <b>Android</b>
內容二
:::
::::
MD);

    expect($html)
        ->toContain('<div class="md-tabs" x-data="{ tab: 0 }">')
        ->toContain('role="tablist" x-cloak')
        ->toContain('>iPhone / iPad</button>')
        ->toContain('&lt;b&gt;Android&lt;/b&gt;')
        ->toContain(':aria-selected="tab === 0"')
        ->toContain('x-show="tab === 1"')
        ->toContain('data-tab-index="0"')
        ->toContain('data-tab-index="1"');
});

test('nested tabs render independently with unique panel ids', function () {
    $html = ($this->convert)(<<<'MD'
::::::tabs
:::::tab 外層
::::tabs
:::tab 內層一
深層內容
:::
:::tab 內層二
更多
:::
::::
:::::
::::::
MD);

    expect(substr_count($html, 'class="md-tabs"'))->toBe(2)
        ->and($html)->toContain('內層一')
        ->and($html)->toContain('深層內容')
        ->and($html)->toContain('內層二');

    preg_match_all('/id="(md-tab-\d+-\d+)"/', $html, $matches);
    expect($matches[1])->toBe(array_unique($matches[1]));
});

// --- 2.6 cards -----------------------------------------------------------

test('cards render each list item as a title + description link', function () {
    $html = ($this->convert)(<<<'MD'
:::cards
- [空大要怎麼上課？](/kb/how-to-study) — 從註冊到考試的完整流程
- [要買書嗎？](/kb/textbooks) — 教科書取得方式比較
:::
MD);

    expect($html)
        ->toContain('<div class="md-cards">')
        ->toContain('<a class="md-card" href="/kb/how-to-study">')
        ->toContain('<span class="md-card-title">空大要怎麼上課？</span>')
        ->toContain('<span class="md-card-desc">從註冊到考試的完整流程</span>');
});

// --- 2.7 timeline ----------------------------------------------------------

test('timeline splits a bold label from its body', function () {
    $html = ($this->convert)(<<<'MD'
:::timeline
- **8 月**：註冊選課開始
- **9 月**：開學、第一次面授
:::
MD);

    expect($html)
        ->toContain('<ol class="md-timeline">')
        ->toContain('<span class="md-timeline-dot" aria-hidden="true"></span>')
        ->toContain('<span class="md-timeline-label"><strong>8 月</strong></span>註冊選課開始');
});

// --- 2.8 summary -----------------------------------------------------------

test('summary defaults its title and wraps its body', function () {
    $html = ($this->convert)(":::summary\n這是懶人包的內容。\n:::\n");

    expect($html)
        ->toContain('<aside class="md-summary"><p class="md-summary-title">懶人包</p>')
        ->toContain('<div class="md-summary-body"><p>這是懶人包的內容。</p></div>');
});

// --- 2.9 cta -----------------------------------------------------------------

test('cta renders links as buttons with the requested variant', function () {
    $html = ($this->convert)(":::cta secondary\n[開始建立我的課表](/schedules)\n:::\n");

    expect($html)
        ->toContain('<div class="md-cta" data-variant="secondary">')
        ->toContain('href="/schedules"')
        ->toContain('border-warm-500 bg-white text-warm-900')
        ->toContain('no-underline')
        ->toContain('>開始建立我的課表</a>');
});

// --- 2.10 figure -----------------------------------------------------------

test('figure wraps an image with a caption', function () {
    $html = ($this->convert)(":::figure 手機上的訂閱畫面\n![訂閱畫面](images/calendar-subscription-google-ios-1.png)\n:::\n");

    expect($html)
        ->toContain('<figure class="md-figure">')
        ->toContain('src="images/calendar-subscription-google-ios-1.png"')
        ->toContain('alt="訂閱畫面"')
        ->toContain('<figcaption class="md-figcaption">手機上的訂閱畫面</figcaption>');
});

// --- 2.11 checklist ----------------------------------------------------------

test('checklist wraps a GFM task list with disabled checkboxes', function () {
    $html = ($this->convert)(":::checklist\n- [ ] 待辦一\n- [x] 已完成\n:::\n");

    expect($html)
        ->toContain('<div class="md-checklist">')
        ->toContain('disabled')
        ->toContain('checked');
});

// --- 2.12 inline / document level -------------------------------------------

test('==mark== renders as a mark element', function () {
    $html = ($this->convert)('這是 ==重點== 文字。');

    expect($html)->toContain('這是 <mark class="md-mark">重點</mark> 文字。');
});

test('heading anchors get chinese-safe deduped slugs', function () {
    $html = ($this->convert)("## 第一段\n\n## 第一段\n");

    expect($html)
        ->toContain('<h2 id="第一段">第一段<a class="md-heading-anchor" href="#第一段" aria-label="連結到此段落">#</a></h2>')
        ->toContain('id="第一段-2"');
});

test('[[toc]] builds a nav from h2/h3 headings', function () {
    $html = ($this->convert)("## 第一段\n\n[[toc]]\n\n## 第二段\n\n### 子段落\n");

    expect($html)
        ->toContain('<nav class="md-toc" aria-label="目錄">')
        ->toContain('<p class="md-toc-title">目錄</p>')
        ->toContain('<a href="#第一段">第一段</a>')
        ->toContain('<a href="#第二段">第二段</a>')
        ->toContain('<a href="#子段落">子段落</a>');
});

test('[[toc]] excludes headings nested inside a blockquote, but they keep their own anchor', function () {
    $html = ($this->convert)(<<<'MD'
## 第一段

[[toc]]

> ### 巢狀標題
> 內容

## 第二段
MD);

    expect($html)->toContain('<h3 id="巢狀標題">巢狀標題<a class="md-heading-anchor" href="#巢狀標題" aria-label="連結到此段落">#</a></h3>');

    preg_match('/<nav class="md-toc"[^>]*>.*?<\/nav>/s', $html, $matches);
    expect($matches)->not->toBeEmpty();
    $toc = $matches[0];

    expect($toc)
        ->toContain('第一段')
        ->toContain('第二段')
        ->not->toContain('巢狀標題');
});

test('[[toc]] excludes faq questions, which are consumed into details/summary instead of real headings', function () {
    $html = ($this->convert)(<<<'MD'
## 第一段

[[toc]]

:::faq
### 巢狀問題
巢狀答案
:::
MD);

    expect($html)->toContain('<summary class="md-faq-question">巢狀問題</summary>');

    preg_match('/<nav class="md-toc"[^>]*>.*?<\/nav>/s', $html, $matches);
    expect($matches)->not->toBeEmpty();
    expect($matches[0])->not->toContain('巢狀問題');
});

test('gfm tables, strikethrough, autolinks and task lists are enabled', function () {
    $html = ($this->convert)(<<<'MD'
| 欄位 | 說明 |
| --- | --- |
| A | B |

~~刪除線~~

<https://example.com>

- [ ] 待辦
MD);

    expect($html)
        ->toContain('<table>')
        ->toContain('<del>刪除線</del>')
        ->toContain('>https://example.com</a>')
        ->toContain('type="checkbox"');
});

test('a bare url autolinks when immediately preceded by cjk punctuation', function () {
    $html = ($this->convert)('示範：https://example.com');

    expect($html)->toContain('<a target="_blank" rel="noopener noreferrer" class="md-link-external" href="https://example.com">https://example.com</a>');
});

test('a bare url autolinks when immediately preceded by a cjk character with no space', function () {
    $html = ($this->convert)('看看這個網站https://example.com。');

    expect($html)
        ->toContain('網站<a')
        ->toContain('>https://example.com</a>。')
        ->not->toContain('。</a>');
});

test('a bare url inside a code span is not autolinked even after cjk punctuation', function () {
    $html = ($this->convert)('範例：`https://example.com`');

    expect($html)
        ->toContain('<code>https://example.com</code>')
        ->not->toContain('<a');
});

test('a bare url inside a fenced code block is not autolinked even after cjk punctuation', function () {
    $html = ($this->convert)("說明：\n\n```\nhttps://example.com\n```\n");

    expect($html)
        ->toContain('<pre><code>https://example.com')
        ->not->toContain('<a href="https://example.com">');
});

test('an already markdown-linked url after cjk punctuation is not double-wrapped', function () {
    $html = ($this->convert)('參考：[官網](https://example.com)');

    expect(substr_count($html, '<a '))->toBe(1)
        ->and($html)->toContain('<a target="_blank" rel="noopener noreferrer" class="md-link-external" href="https://example.com">官網</a>');
});

test('external links get target blank and rel noopener noreferrer', function () {
    $html = ($this->convert)('[Google](https://www.google.com/)');

    expect($html)
        ->toContain('target="_blank"')
        ->toContain('rel="noopener noreferrer"')
        ->toContain('class="md-link-external"');
});

test('internal links are left untouched', function () {
    $html = ($this->convert)('[Home](/)');

    expect($html)
        ->not->toContain('target="_blank"')
        ->not->toContain('md-link-external');
});

test('an html comment produces no visible output', function () {
    $html = ($this->convert)("<!-- placeholder: 內容待校對 -->\n\n內文。\n");

    expect($html)
        ->not->toContain('placeholder')
        ->not->toContain('內容待校對')
        ->not->toContain('<!--')
        ->toContain('<p>內文。</p>');
});

test('a raw script tag produces no output', function () {
    $html = ($this->convert)("<script>alert(1)</script>\n\n內文。\n");

    expect($html)
        ->not->toContain('<script')
        ->not->toContain('alert(1)')
        ->toContain('<p>內文。</p>');
});

// --- 1. generic container semantics ------------------------------------------

test('an unknown container name renders as a plain div without losing content', function () {
    $html = ($this->convert)(":::mystery arg\n這段內容不能不見。\n:::\n");

    expect($html)
        ->toContain('<div class="md-block">')
        ->toContain('這段內容不能不見。')
        ->not->toContain('mystery');
});

test('an unclosed container implicitly closes at end of document', function () {
    $html = ($this->convert)(":::summary 沒有結尾\n內容仍然要出現\n");

    expect($html)
        ->toContain('<aside class="md-summary">')
        ->toContain('內容仍然要出現');
});

test('an unclosed dialogue container still renders its last turn', function () {
    $html = ($this->convert)(":::dialogue\n新生：沒有關閉的容器\n");

    expect($html)
        ->toContain('<p class="md-dialogue-speaker">新生</p>')
        ->toContain('沒有關閉的容器');
});

// --- raw markdown passthrough -------------------------------------------------

test('the raw markdown source stays byte-identical for custom syntax', function () {
    $slug = 'welcome';
    $path = resource_path('articles/manual/'.$slug.'.md');

    if (! File::exists($path)) {
        $this->markTestSkipped('Fixture article missing: '.$path);
    }

    $raw = File::get($path);
    $expectedBody = (new FrontMatterParser(new SymfonyYamlFrontMatterParser))->parse($raw)->getContent();

    $response = $this->get(route('articles.show.md', [
        'type' => ArticleType::MANUAL->value,
        'slug' => $slug,
    ]));

    $response->assertSuccessful();
    expect($response->getContent())->toContain($expectedBody);
});

test('custom block syntax passes through the raw markdown endpoint untouched', function () {
    File::shouldReceive('exists')->andReturn(true);
    File::shouldReceive('get')->andReturn(<<<'MD'
---
title: Test
author: Test
published_at: 2020-01-01
---

:::dialogue
新生：這段語法應該原封不動地出現。
:::
MD);

    $response = $this->get(route('articles.show.md', [
        'type' => ArticleType::MANUAL->value,
        'slug' => 'test-slug',
    ]));

    $response->assertSuccessful()
        ->assertSee(':::dialogue', false)
        ->assertSee('新生：這段語法應該原封不動地出現。', false)
        ->assertDontSee('md-dialogue', false);
});
