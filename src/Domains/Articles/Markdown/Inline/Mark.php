<?php

declare(strict_types=1);

namespace NouTools\Domains\Articles\Markdown\Inline;

use League\CommonMark\Node\Inline\AbstractInline;
use League\CommonMark\Node\Inline\DelimitedInterface;

final class Mark extends AbstractInline implements DelimitedInterface
{
    public function getOpeningDelimiter(): string
    {
        return '==';
    }

    public function getClosingDelimiter(): string
    {
        return '==';
    }
}
