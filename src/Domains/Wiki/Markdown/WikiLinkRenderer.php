<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Markdown;

use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;
use NouTools\Domains\Articles\Markdown\Heading\HeadingSlugger;

/**
 * A link to a live page shows its current title unless a label is given. A
 * link to a page that doesn't exist yet is a red link to the new-page form.
 */
final class WikiLinkRenderer implements NodeRendererInterface
{
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): \Stringable
    {
        if (! $node instanceof WikiLink) {
            throw new \InvalidArgumentException('Incompatible node type: '.$node::class);
        }

        if ($node->target === null) {
            return new HtmlElement('a', [
                'href' => '/wiki/new?slug='.rawurlencode($node->slug),
                'class' => 'wiki-link wiki-link-missing',
                'title' => '尚未建立的條目',
                'data-wiki-slug' => $node->slug,
            ], e($node->label ?? $node->slug));
        }

        $anchor = $node->anchor !== null ? '#'.rawurlencode((new HeadingSlugger)->slugify($node->anchor)) : '';

        return new HtmlElement('a', [
            'href' => '/wiki/'.$node->target->slug.$anchor,
            'class' => 'wiki-link',
            'data-wiki-slug' => $node->target->slug,
        ], e($node->label ?? $node->target->title));
    }
}
