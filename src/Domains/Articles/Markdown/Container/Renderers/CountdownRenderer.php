<?php

declare(strict_types=1);

namespace NouTools\Domains\Articles\Markdown\Container\Renderers;

use Illuminate\Support\Facades\Date;
use League\CommonMark\Extension\CommonMark\Node\Inline\Strong;
use League\CommonMark\Node\Block\Paragraph;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Util\HtmlElement;
use League\CommonMark\Util\Xml;
use NouTools\Domains\Articles\Markdown\Container\ContainerNode;
use NouTools\Domains\Articles\Markdown\Container\ContainerRendererInterface;
use NouTools\Domains\Articles\Markdown\Support\InlineTextExtractor;

/**
 * `:::countdown` renders one live countdown per paragraph shaped like
 * `**label**: YYYY-MM-DD` or `**label**: YYYY-MM-DD ~ YYYY-MM-DD`. The day
 * count is rendered server-side against "today" in Asia/Taipei (matching
 * how school-calendar dates are anchored elsewhere in the app, see
 * `ListUpcomingSchoolEvents`) and then kept live client-side by
 * `window.nouToolsCountdown` in app.js, so it still works without JavaScript.
 */
final class CountdownRenderer implements ContainerRendererInterface
{
    private const DATE_PATTERN = '/^\s*[：:]\s*(\d{4}-\d{2}-\d{2})(?:\s*~\s*(\d{4}-\d{2}-\d{2}))?/u';

    public function render(ContainerNode $node, ChildNodeRendererInterface $childRenderer): \Stringable
    {
        $items = [];

        foreach ($node->children() as $child) {
            if (! $child instanceof Paragraph) {
                continue;
            }

            $items[] = $this->buildItem($child, $childRenderer);
        }

        return new HtmlElement('div', ['class' => 'md-countdown'], $items);
    }

    private function buildItem(Paragraph $paragraph, ChildNodeRendererInterface $childRenderer): HtmlElement
    {
        $firstInline = $paragraph->firstChild();
        $secondInline = $firstInline?->next();

        if ($firstInline instanceof Strong && $secondInline !== null) {
            $afterText = InlineTextExtractor::plainTextFrom($secondInline);

            if (\preg_match(self::DATE_PATTERN, $afterText, $matches)) {
                $label = InlineTextExtractor::plainText($firstInline);
                $start = $matches[1];
                $end = $matches[2] ?? $start;

                return $this->buildLiveItem($label, $start, $end);
            }
        }

        return new HtmlElement('div', ['class' => 'md-countdown-item'], $childRenderer->renderNodes([$paragraph]));
    }

    private function buildLiveItem(string $label, string $start, string $end): HtmlElement
    {
        $rangeText = $start === $end ? $start : "{$start} ~ {$end}";

        $config = (string) \json_encode([
            'start' => $start,
            'end' => $end,
        ], \JSON_THROW_ON_ERROR);

        return new HtmlElement('div', [
            'class' => 'md-countdown-item',
            'x-data' => 'nouToolsCountdown('.$config.')',
        ], [
            new HtmlElement('p', ['class' => 'md-countdown-label'], Xml::escape($label)),
            new HtmlElement('p', ['class' => 'md-countdown-range'], Xml::escape($rangeText)),
            new HtmlElement('p', ['class' => 'md-countdown-days'], [
                new HtmlElement('span', ['x-text' => 'daysText'], Xml::escape($this->daysText($start, $end))),
            ]),
        ]);
    }

    private function daysText(string $start, string $end): string
    {
        $today = Date::now('Asia/Taipei')->format('Y-m-d');

        if ($today < $start) {
            $days = Date::parse($today, 'Asia/Taipei')->startOfDay()
                ->diffInDays(Date::parse($start, 'Asia/Taipei')->startOfDay());

            return "倒數 {$days} 天";
        }

        if ($today <= $end) {
            return '進行中';
        }

        return '已結束';
    }
}
