<?php

declare(strict_types=1);

namespace NouTools\Domains\Articles\Markdown\Container\Renderers;

use App\View\Components\Button;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Util\HtmlElement;
use NouTools\Domains\Articles\Markdown\Container\ContainerNode;
use NouTools\Domains\Articles\Markdown\Container\ContainerRendererInterface;

final class CtaRenderer implements ContainerRendererInterface
{
    public function render(ContainerNode $node, ChildNodeRendererInterface $childRenderer): \Stringable
    {
        $variant = \trim((string) $node->getArgument()) === 'secondary' ? 'secondary' : 'primary';
        $buttonClasses = (new Button(variant: $variant, class: 'no-underline'))->getClasses();

        $buttons = [];

        foreach ($node->iterator() as $child) {
            if ($child instanceof Link) {
                $buttons[] = new HtmlElement('a', [
                    'class' => $buttonClasses,
                    'href' => $child->getUrl(),
                ], $childRenderer->renderNodes($child->children()));
            }
        }

        return new HtmlElement('div', ['class' => 'md-cta', 'data-variant' => $variant], $buttons);
    }
}
