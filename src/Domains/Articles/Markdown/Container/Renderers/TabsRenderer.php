<?php

declare(strict_types=1);

namespace NouTools\Domains\Articles\Markdown\Container\Renderers;

use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Util\HtmlElement;
use League\CommonMark\Util\Xml;
use NouTools\Domains\Articles\Markdown\Container\ContainerNode;
use NouTools\Domains\Articles\Markdown\Container\ContainerRendererInterface;

/**
 * `:::tabs` emits a plain tablist plus one panel per `:::tab`, marked up with
 * `data-*` only — the interactive behaviour is attached client-side by
 * `useMarkdownContainers` (resources/js/Composables), which is also what sets
 * `data-enhanced` on the wrapper.
 *
 * No panel is emitted `hidden`, on purpose: without JavaScript every panel
 * stays readable and CSS suppresses the tab strip instead (see the
 * `.md-tabs:not([data-enhanced])` rule in resources/css/app.css), so the
 * content is never hidden behind a control that can't work.
 */
final class TabsRenderer implements ContainerRendererInterface
{
    public function render(ContainerNode $node, ChildNodeRendererInterface $childRenderer): \Stringable
    {
        $blockIndex = $node->getStartLine() ?? 0;

        $triggers = [];
        $panels = [];
        $index = 0;

        foreach ($node->children() as $child) {
            if (! $child instanceof ContainerNode || $child->getName() !== 'tab') {
                continue;
            }

            $title = $child->getArgument() ?? '';
            $panelId = "md-tab-{$blockIndex}-{$index}";
            $tabId = "{$panelId}-trigger";

            $triggers[] = new HtmlElement('button', [
                'type' => 'button',
                'class' => 'md-tabs-trigger',
                'role' => 'tab',
                'id' => $tabId,
                'aria-controls' => $panelId,
                'aria-selected' => $index === 0 ? 'true' : 'false',
                'data-active' => $index === 0 ? 'true' : 'false',
            ], Xml::escape($title));

            $panels[] = new HtmlElement('div', [
                'class' => 'md-tabs-panel',
                'role' => 'tabpanel',
                'id' => $panelId,
                'aria-labelledby' => $tabId,
                'data-tab-index' => (string) $index,
            ], $childRenderer->renderNodes($child->children()));

            $index++;
        }

        $tabList = new HtmlElement('div', [
            'class' => 'md-tabs-list',
            'role' => 'tablist',
        ], $triggers);

        return new HtmlElement('div', [
            'class' => 'md-tabs',
        ], [$tabList, ...$panels]);
    }
}
