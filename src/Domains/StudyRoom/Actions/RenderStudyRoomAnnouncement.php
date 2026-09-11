<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\Actions;

use Illuminate\Support\HtmlString;
use NouTools\Domains\Articles\Markdown\ArticleMarkdownConverterFactory;

/**
 * Renders the admin-authored `StudyRoomSettings::$announcement` Markdown to
 * HTML, reusing the shared article Markdown pipeline (which already escapes
 * raw HTML input and disallows unsafe links) rather than a bespoke one.
 */
final readonly class RenderStudyRoomAnnouncement
{
    public function __construct(private ArticleMarkdownConverterFactory $converterFactory) {}

    public function __invoke(string $markdown): HtmlString
    {
        return new HtmlString($this->converterFactory->make()->convert($markdown)->getContent());
    }
}
