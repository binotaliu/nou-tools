<?php

declare(strict_types=1);

namespace NouTools\Domains\Articles\Markdown\Container\Renderers;

use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Util\HtmlElement;
use NouTools\Domains\Articles\Markdown\Container\ContainerNode;
use NouTools\Domains\Articles\Markdown\Container\ContainerRendererInterface;

/**
 * `:::checklist` turns Markdown's read-only GFM task list into an interactive
 * one: the `disabled` attribute is stripped and each item's content is wrapped
 * in a `<label>` so the whole row is clickable, both server-side here.
 *
 * That leaves only persistence to the client — `useMarkdownContainers`
 * (resources/js/Composables) restores each box from localStorage and tracks
 * `data-checked` for styling — so the list is usable, if forgetful, with no
 * JavaScript at all.
 */
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
        ], $content);
    }
}
