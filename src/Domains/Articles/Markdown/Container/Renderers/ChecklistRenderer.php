<?php

declare(strict_types=1);

namespace NouTools\Domains\Articles\Markdown\Container\Renderers;

use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Util\HtmlElement;
use NouTools\Domains\Articles\Markdown\Container\ContainerNode;
use NouTools\Domains\Articles\Markdown\Container\ContainerRendererInterface;

final class ChecklistRenderer implements ContainerRendererInterface
{
    public function render(ContainerNode $node, ChildNodeRendererInterface $childRenderer): \Stringable
    {
        $content = (string) $childRenderer->renderNodes($node->children());
        $content = (string) \preg_replace('/\sdisabled(?:="")?(?=[\s>])/', '', $content);
        $content = (string) \preg_replace_callback(
            '/<li([^>]*)>(\s*<input\b[^>]*>)(\s*.*?)<\/li>/s',
            static fn (array $matches): string => '<li'.$matches[1].'><label>'.$matches[2].'<span class="md-checklist-content">'.$matches[3].'</span></label></li>',
            $content,
        );

        return new HtmlElement('div', [
            'class' => 'md-checklist',
            'x-data' => 'nouChecklist()',
        ], $content);
    }
}
