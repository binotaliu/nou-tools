<?php

declare(strict_types=1);

namespace NouTools\Domains\Articles\Markdown\Embed;

use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\CommonMark\Node\Block\HtmlBlock;

/**
 * Lets authors paste YouTube's "embed" snippet straight into Markdown even
 * though raw HTML is otherwise stripped (`markdown.commonmark.html_input`).
 *
 * Only a block that is exactly one `<iframe ...></iframe>` pointing at
 * `/embed/{11-char id}` on a YouTube host is recognised. Nothing else from the
 * pasted tag is trusted: the video id, an optional `start` offset and the
 * title are pulled out and the iframe is rebuilt by YoutubeEmbedRenderer, so
 * extra attributes (`onload`, `srcdoc`, a different `src` host...) are dropped.
 * Any other HTML block is left for the configured `html_input` handling.
 */
final class YoutubeEmbedProcessor
{
    /** The single origin the rebuilt iframe loads; PublicSitePolicy allows it in `frame-src`. */
    public const string EMBED_ORIGIN = 'https://www.youtube-nocookie.com';

    private const array HOSTS = [
        'youtube.com',
        'www.youtube.com',
        'youtube-nocookie.com',
        'www.youtube-nocookie.com',
    ];

    private const string DEFAULT_TITLE = 'YouTube 影片';

    public function __invoke(DocumentParsedEvent $event): void
    {
        $htmlBlocks = [];

        foreach ($event->getDocument()->iterator() as $node) {
            if ($node instanceof HtmlBlock) {
                $htmlBlocks[] = $node;
            }
        }

        foreach ($htmlBlocks as $htmlBlock) {
            $embed = $this->embedFrom($htmlBlock->getLiteral());

            if ($embed !== null) {
                $htmlBlock->replaceWith($embed);
            }
        }
    }

    private function embedFrom(string $html): ?YoutubeEmbedNode
    {
        if (! preg_match('~^<iframe\s+([^<>]*)>\s*</iframe>$~is', trim($html), $tag)) {
            return null;
        }

        $attributes = $this->attributes($tag[1]);

        $url = parse_url($attributes['src'] ?? '');

        if ($url === false
            || ($url['scheme'] ?? 'https') !== 'https'
            || ! in_array(strtolower($url['host'] ?? ''), self::HOSTS, true)
            || ! preg_match('~^/embed/(?!videoseries$)([A-Za-z0-9_-]{11})$~', $url['path'] ?? '', $path)) {
            return null;
        }

        parse_str($url['query'] ?? '', $query);

        $start = $query['start'] ?? null;

        return new YoutubeEmbedNode(
            videoId: $path[1],
            title: trim($attributes['title'] ?? '') ?: self::DEFAULT_TITLE,
            startSeconds: is_string($start) && ctype_digit($start) && $start !== '0' ? (int) $start : null,
        );
    }

    /**
     * @return array<string, string> lowercase attribute name => decoded value
     */
    private function attributes(string $attributeString): array
    {
        preg_match_all('~([a-z][a-z-]*)\s*=\s*(?:"([^"]*)"|\'([^\']*)\')~i', $attributeString, $matches, PREG_SET_ORDER);

        $attributes = [];

        foreach ($matches as $match) {
            $attributes[strtolower($match[1])] = html_entity_decode($match[2] ?: ($match[3] ?? ''), ENT_QUOTES | ENT_HTML5);
        }

        return $attributes;
    }
}
