<?php

declare(strict_types=1);

use App\Models\WikiPage;
use App\Models\WikiSlugRedirect;
use NouTools\Domains\Wiki\Actions\InspectWikiMarkdown;
use NouTools\Domains\Wiki\Actions\RenderWikiMarkdown;

beforeEach(function (): void {
    $this->render = fn (string $markdown): string => app(RenderWikiMarkdown::class)($markdown);
});

it('links to a live page with its title', function (): void {
    WikiPage::factory()->live(['title' => '空大考試要去哪裡考？'])->create(['slug' => 'nou-exams-info']);

    expect(($this->render)('詳見[[nou-exams-info]]。'))
        ->toContain('<a class="wiki-link" href="/wiki/nou-exams-info">空大考試要去哪裡考？</a>');
});

it('uses the label and turns the anchor into a heading id', function (): void {
    WikiPage::factory()->live()->create(['slug' => 'nou-exams-info']);

    expect(($this->render)('[[nou-exams-info#期中 考試|考試時間]]'))
        ->toContain('href="/wiki/nou-exams-info#'.rawurlencode('期中-考試').'"')
        ->toContain('>考試時間</a>');
});

it('renders a red link to the new page form for a missing page', function (): void {
    expect(($this->render)('[[nou-credits|學分抵免]]'))
        ->toContain('href="/wiki/new?slug=nou-credits"')
        ->toContain('wiki-link-missing')
        ->toContain('>學分抵免</a>');
});

it('follows a slug redirect to the current slug', function (): void {
    $page = WikiPage::factory()->live(['title' => '考試'])->create(['slug' => 'nou-exams-info']);
    $redirect = new WikiSlugRedirect;
    $redirect->old_slug = 'exams';
    $redirect->wiki_page_id = $page->id;
    $redirect->save();

    expect(($this->render)('[[exams]]'))->toContain('href="/wiki/nou-exams-info"');
});

it('does not treat a page without a revision as live', function (): void {
    WikiPage::factory()->create(['slug' => 'draft-page']);

    expect(($this->render)('[[draft-page]]'))->toContain('wiki-link-missing');
});

it('leaves non-slug brackets and the toc placeholder alone', function (): void {
    expect(($this->render)('[[空大]]'))->toContain('[[空大]]')->not->toContain('wiki-link')
        ->and(($this->render)("## 標題\n\n[[toc]]"))->not->toContain('wiki-link');
});

it('escapes labels', function (): void {
    expect(($this->render)('[[nou-credits|<script>x</script>]]'))
        ->toContain('&lt;script&gt;')
        ->not->toContain('<script>');
});

it('renders footnotes as a references section', function (): void {
    $html = ($this->render)("期中考在週末舉行。[^1]\n\n[^1]: [114學年度行事曆](https://www.nou.edu.tw/calendar)");

    expect($html)
        ->toContain('<section class="footnotes wiki-references"')
        ->toContain('<h2 id="wiki-references">參考資料</h2>')
        ->toContain('href="https://www.nou.edu.tw/calendar"')
        ->not->toContain('<hr');
});

it('keeps a leading thematic break instead of reading it as front matter', function (): void {
    expect(($this->render)("---\ntitle: x\n---\n\n內文"))->toContain('<hr />');
});

it('strips raw html', function (): void {
    expect(($this->render)('<img src=x onerror=alert(1)>'))->not->toContain('<img');
});

it('reports wiki links and external urls', function (): void {
    $result = app(InspectWikiMarkdown::class)(
        "[[nou-exams-info]] 與 [[nou-credits|抵免]]。[^1]\n\n```\n[[in-code]]\n```\n\n[^1]: <https://www.nou.edu.tw/a>",
    );

    expect($result['wikiLinks'])->toBe(['nou-exams-info', 'nou-credits'])
        ->and($result['externalUrls'])->toBe(['https://www.nou.edu.tw/a']);
});

it('renders wiki links inside cards', function (): void {
    WikiPage::factory()->live(['title' => '考試'])->create(['slug' => 'nou-exams-info']);

    expect(($this->render)(":::cards\n- [[nou-exams-info|考試怎麼考？]] — 考場與時間\n:::\n"))
        ->toContain('<a class="md-card" href="/wiki/nou-exams-info">')
        ->toContain('考試怎麼考？');
});

it('renders every documented example of the guides without losing a wiki link', function (string $guide): void {
    $html = ($this->render)(file_get_contents(resource_path("wiki/guides/{$guide}.md")));

    expect($html)->not->toBeEmpty()->not->toContain('<script');
})->with(['writing-guide', 'syntax-guide']);
