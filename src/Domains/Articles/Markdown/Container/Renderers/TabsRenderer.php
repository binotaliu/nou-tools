<?php

declare(strict_types=1);

namespace NouTools\Domains\Articles\Markdown\Container\Renderers;

use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Util\HtmlElement;
use League\CommonMark\Util\Xml;
use NouTools\Domains\Articles\Markdown\Container\ContainerNode;
use NouTools\Domains\Articles\Markdown\Container\ContainerRendererInterface;

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
                ':aria-selected' => "tab === {$index}",
                ':data-active' => "tab === {$index}",
                '@click' => "tab = {$index}",
            ], Xml::escape($title));

            $panels[] = new HtmlElement('div', [
                'class' => 'md-tabs-panel',
                'role' => 'tabpanel',
                'id' => $panelId,
                'aria-labelledby' => $tabId,
                'x-show' => "tab === {$index}",
                'data-tab-index' => (string) $index,
            ], $childRenderer->renderNodes($child->children()));

            $index++;
        }

        $tabList = new HtmlElement('div', [
            'class' => 'md-tabs-list',
            'role' => 'tablist',
            'x-cloak' => true,
        ], $triggers);

        return new HtmlElement('div', [
            'class' => 'md-tabs',
            'x-data' => '{ tab: 0 }',
        ], [$tabList, ...$panels]);
    }
}
