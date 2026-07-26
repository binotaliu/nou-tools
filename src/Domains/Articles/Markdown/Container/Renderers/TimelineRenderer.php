<?php

declare(strict_types=1);

namespace NouTools\Domains\Articles\Markdown\Container\Renderers;

use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Extension\CommonMark\Node\Block\ListBlock;
use League\CommonMark\Extension\CommonMark\Node\Block\ListItem;
use League\CommonMark\Extension\CommonMark\Node\Inline\Strong;
use League\CommonMark\Node\Block\Paragraph;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Util\HtmlElement;
use League\CommonMark\Util\Xml;
use NouTools\Domains\Articles\Markdown\Container\ContainerNode;
use NouTools\Domains\Articles\Markdown\Container\ContainerRendererInterface;
use NouTools\Domains\Articles\Markdown\Support\InlineTextExtractor;

final class TimelineRenderer implements ContainerRendererInterface
{
    public function render(ContainerNode $node, ChildNodeRendererInterface $childRenderer): \Stringable
    {
        $title = $node->getArgument();
        $elements = [];

        // Add optional title
        if ($title !== null && $title !== '') {
            $elements[] = new HtmlElement('div', ['class' => 'md-timeline-title'], Xml::escape($title));
        }

        // Process children to build timeline structure
        $elements = [...$elements, ...$this->processChildren($node, $childRenderer)];

        return new HtmlElement('div', ['class' => 'md-timeline'], $elements);
    }

    /**
     * @return array<HtmlElement>
     */
    private function processChildren(ContainerNode $node, ChildNodeRendererInterface $childRenderer): array
    {
        $elements = [];
        $currentSection = null;
        $currentSectionContent = [];
        $currentSectionItems = [];
        $flatItems = [];

        foreach ($node->children() as $child) {
            if ($child instanceof Heading) {
                // Save previous section if exists
                if ($currentSection !== null) {
                    $elements[] = $this->buildSection($currentSection, $currentSectionContent, $currentSectionItems, $childRenderer);
                    $currentSectionContent = [];
                    $currentSectionItems = [];
                }

                $currentSection = $child;
            } elseif ($child instanceof ListBlock) {
                // If we have a current section, add items to it
                if ($currentSection !== null) {
                    foreach ($child->children() as $listItem) {
                        if ($listItem instanceof ListItem) {
                            $currentSectionItems[] = $listItem;
                        }
                    }
                } else {
                    // No section, collect flat items for backward compatibility
                    foreach ($child->children() as $listItem) {
                        if ($listItem instanceof ListItem) {
                            $flatItems[] = $listItem;
                        }
                    }
                }
            } else {
                // Any other content (paragraphs, etc.) goes to the current section
                if ($currentSection !== null) {
                    $currentSectionContent[] = $child;
                }
            }
        }

        // Save last section if exists
        if ($currentSection !== null) {
            $elements[] = $this->buildSection($currentSection, $currentSectionContent, $currentSectionItems, $childRenderer);
        }

        // If we only have flat items (no sections), render as old-style timeline
        if ($flatItems !== [] && $elements === []) {
            $itemElements = [];
            foreach ($flatItems as $listItem) {
                $itemElements[] = $this->buildItem($listItem, $childRenderer, false);
            }

            return [new HtmlElement('ol', ['class' => 'md-timeline-flat'], $itemElements)];
        }

        return $elements;
    }

    /**
     * @param  array<Node>  $content
     * @param  array<ListItem>  $items
     */
    private function buildSection(Heading $heading, array $content, array $items, ChildNodeRendererInterface $childRenderer): HtmlElement
    {
        $sectionTitle = new HtmlElement('div', ['class' => 'md-timeline-section-title'], $childRenderer->renderNodes($heading->children()));

        $sectionElements = [];

        // Add any content between heading and list
        if ($content !== []) {
            $sectionElements[] = new HtmlElement('div', ['class' => 'md-timeline-section-content'], $childRenderer->renderNodes($content));
        }

        // Add timeline items
        if ($items !== []) {
            $itemElements = [];
            foreach ($items as $listItem) {
                $itemElements[] = $this->buildItem($listItem, $childRenderer, true);
            }
            $sectionElements[] = new HtmlElement('ol', ['class' => 'md-timeline-section-items'], $itemElements);
        }

        return new HtmlElement('div', ['class' => 'md-timeline-section'], [
            $sectionTitle,
            ...$sectionElements,
        ]);
    }

    private function buildItem(ListItem $listItem, ChildNodeRendererInterface $childRenderer, bool $inSection): HtmlElement
    {
        $dot = new HtmlElement('span', ['class' => 'md-timeline-dot', 'aria-hidden' => 'true']);
        $body = $this->buildBody($listItem, $childRenderer);

        $class = $inSection ? 'md-timeline-section-item' : 'md-timeline-item';

        return new HtmlElement('li', ['class' => $class], [
            $dot,
            new HtmlElement('div', ['class' => 'md-timeline-body'], $body),
        ]);
    }

    private function buildBody(ListItem $listItem, ChildNodeRendererInterface $childRenderer): string
    {
        $paragraph = null;

        foreach ($listItem->children() as $child) {
            if ($child instanceof Paragraph) {
                $paragraph = $child;

                break;
            }
        }

        if ($paragraph === null) {
            return $childRenderer->renderNodes($listItem->children());
        }

        $firstInline = $paragraph->firstChild();
        $secondInline = $firstInline?->next();

        // Support **label**: text format (backward compatibility)
        if ($firstInline instanceof Strong && $secondInline !== null) {
            $afterText = InlineTextExtractor::plainTextFrom($secondInline);

            if (\preg_match('/^\s*[：:]/u', $afterText)) {
                $label = new HtmlElement('span', ['class' => 'md-timeline-label'], $childRenderer->renderNodes([$firstInline]));
                $remainder = InlineTextExtractor::stripLeadingSeparator($afterText);

                return $label.Xml::escape($remainder);
            }
        }

        return $childRenderer->renderNodes($listItem->children());
    }
}
