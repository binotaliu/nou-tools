<?php

declare(strict_types=1);

namespace NouTools\Domains\Articles\Markdown\Container\Renderers;

use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Util\HtmlElement;
use League\CommonMark\Util\Xml;
use NouTools\Domains\Articles\Markdown\Container\ContainerNode;
use NouTools\Domains\Articles\Markdown\Container\ContainerRendererInterface;
use NouTools\Domains\Articles\Markdown\Image\ImageDimensionResolver;
use NouTools\Domains\Articles\Markdown\Support\InlineTextExtractor;

final class FigureRenderer implements ContainerRendererInterface
{
    public function render(ContainerNode $node, ChildNodeRendererInterface $childRenderer): \Stringable
    {
        $caption = $node->getArgument();
        $image = null;

        foreach ($node->iterator() as $child) {
            if ($child instanceof Image) {
                $image = $child;

                break;
            }
        }

        $dimensions = $image !== null ? ImageDimensionResolver::resolve($image->getUrl()) : null;

        $imageContent = $image !== null
            ? new HtmlElement('img', \array_filter([
                'src' => $image->getUrl(),
                'alt' => InlineTextExtractor::plainText($image),
                'width' => isset($dimensions['width']) ? (string) $dimensions['width'] : null,
                'height' => isset($dimensions['height']) ? (string) $dimensions['height'] : null,
                'loading' => 'lazy',
            ], static fn (mixed $value): bool => $value !== null), '', true)
            : $childRenderer->renderNodes($node->children());

        $contents = [$imageContent];

        if ($caption !== null) {
            $contents[] = new HtmlElement('figcaption', ['class' => 'md-figcaption'], Xml::escape($caption));
        }

        return new HtmlElement('figure', ['class' => 'md-figure'], $contents);
    }
}
