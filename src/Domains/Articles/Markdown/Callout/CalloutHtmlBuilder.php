<?php

declare(strict_types=1);

namespace NouTools\Domains\Articles\Markdown\Callout;

use BladeUI\Icons\Exceptions\SvgNotFound;
use League\CommonMark\Util\HtmlElement;
use League\CommonMark\Util\Xml;

final readonly class CalloutHtmlBuilder
{
    /**
     * @param  \Stringable|string  $body  Pre-rendered (already escaped) body HTML
     */
    public function build(string $type, ?string $title, \Stringable|string $body): HtmlElement
    {
        /** @var array{label?: string, icon?: string} $meta */
        $meta = config("markdown.callouts.{$type}", []);

        $label = $title ?? ($meta['label'] ?? \ucfirst($type));

        $titleParagraph = new HtmlElement('p', ['class' => 'md-callout-title'], \array_values(\array_filter([
            $this->renderIcon($meta['icon'] ?? null),
            Xml::escape($label),
        ])));

        $bodyDiv = new HtmlElement('div', ['class' => 'md-callout-body'], $body);

        return new HtmlElement('div', [
            'class' => 'md-callout',
            'data-type' => $type,
        ], [$titleParagraph, $bodyDiv]);
    }

    /**
     * Render the configured Blade Icons (heroicon) name as an inline SVG.
     *
     * Returns null when no icon is configured or the icon set doesn't know the
     * name, so a typo in config degrades to a title-only callout.
     */
    private function renderIcon(?string $name): ?string
    {
        if ($name === null || $name === '') {
            return null;
        }

        try {
            return svg($name, 'md-callout-icon')->toHtml();
        } catch (SvgNotFound) {
            return null;
        }
    }

    public function isKnownType(string $type): bool
    {
        return \array_key_exists($type, config('markdown.callouts', []));
    }
}
