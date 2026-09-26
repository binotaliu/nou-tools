<?php

use NouTools\Domains\Articles\Markdown\ArticleMarkdownConverterFactory;

it('keeps the alt text of an image and renders an empty alt for decorative ones', function () {
    $convert = fn (string $markdown): string => (new ArticleMarkdownConverterFactory)->make()->convert($markdown)->getContent();

    expect($convert('![選課系統首頁](/images/a.png)'))->toContain('alt="選課系統首頁"')
        ->and($convert('![](/images/a.png)'))->toContain('alt=""');
});
