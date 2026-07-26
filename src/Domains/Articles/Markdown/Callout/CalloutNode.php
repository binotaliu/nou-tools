<?php

declare(strict_types=1);

namespace NouTools\Domains\Articles\Markdown\Callout;

use League\CommonMark\Node\Block\AbstractBlock;

final class CalloutNode extends AbstractBlock
{
    public function __construct(
        private readonly string $type,
        private readonly ?string $title,
    ) {
        parent::__construct();
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }
}
