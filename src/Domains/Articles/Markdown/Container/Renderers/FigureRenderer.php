<?php

declare(strict_types=1);

namespace NouTools\Domains\Articles\Markdown\Container\Renderers;

use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Util\HtmlElement;
use League\CommonMark\Util\Xml;
use NouTools\Domains\Articles\Markdown\Container\ContainerNode;
use NouTools\Domains\Articles\Markdown\Container\ContainerRendererInterface;
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

        $imageContent = $image !== null
            ? new HtmlElement('img', [
                'src' => $image->getUrl(),
                'alt' => InlineTextExtractor::plainText($image),
                'loading' => 'lazy',
            ], '', true)
            : $childRenderer->renderNodes($node->children());

        $contents = [$imageContent];

        if ($caption !== null) {
            $contents[] = new HtmlElement('figcaption', ['class' => 'md-figcaption'], Xml::escape($caption));
        }

        return new HtmlElement('figure', ['class' => 'md-figure'], $contents);
    }
}
