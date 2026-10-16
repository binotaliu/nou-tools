<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Actions;

use Illuminate\Support\Facades\File;
use NouTools\Domains\Wiki\PageData\WikiGuidePageData;

/**
 * The writing and syntax guides: repository files, rendered through the wiki
 * pipeline, and the very text the AI editor is given as its rules.
 */
final readonly class ShowWikiGuidePage
{
    public const array GUIDES = [
        'writing' => ['title' => '撰寫指南', 'file' => 'writing-guide.md'],
        'syntax' => ['title' => '語法說明', 'file' => 'syntax-guide.md'],
    ];

    public function __construct(private RenderWikiMarkdown $renderWikiMarkdown) {}

    public function __invoke(string $guide): ?WikiGuidePageData
    {
        if (! isset(self::GUIDES[$guide])) {
            return null;
        }

        $other = $guide === 'writing' ? 'syntax' : 'writing';

        return new WikiGuidePageData(
            guide: $guide,
            title: self::GUIDES[$guide]['title'],
            content: ($this->renderWikiMarkdown)(File::get(resource_path('wiki/guides/'.self::GUIDES[$guide]['file']))),
            otherGuideTitle: self::GUIDES[$other]['title'],
            otherGuideUrl: route('wiki.guide', $other),
        );
    }
}
