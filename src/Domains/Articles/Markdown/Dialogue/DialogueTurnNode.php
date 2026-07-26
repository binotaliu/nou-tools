<?php

declare(strict_types=1);

namespace NouTools\Domains\Articles\Markdown\Dialogue;

use League\CommonMark\Node\Block\AbstractBlock;

final class DialogueTurnNode extends AbstractBlock
{
    public function __construct(
        private readonly string $speaker,
        private readonly ?string $mood,
    ) {
        parent::__construct();
    }

    public function getSpeaker(): string
    {
        return $this->speaker;
    }

    public function getMood(): ?string
    {
        return $this->mood;
    }
}
