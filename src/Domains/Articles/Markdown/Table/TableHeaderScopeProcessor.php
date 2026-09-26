<?php

declare(strict_types=1);

namespace NouTools\Domains\Articles\Markdown\Table;

use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\Table\TableCell;

/**
 * GFM tables only ever have a header row, so every `<th>` heads a column;
 * `scope="col"` lets screen readers announce it for each cell beneath.
 */
final readonly class TableHeaderScopeProcessor
{
    public function __invoke(DocumentParsedEvent $event): void
    {
        foreach ($event->getDocument()->iterator() as $node) {
            if ($node instanceof TableCell && $node->getType() === TableCell::TYPE_HEADER) {
                $node->data->set('attributes/scope', 'col');
            }
        }
    }
}
