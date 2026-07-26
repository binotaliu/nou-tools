<?php

declare(strict_types=1);

namespace NouTools\Domains\Articles\Markdown\Container;

use League\CommonMark\Node\Block\AbstractBlock;

final class ContainerNode extends AbstractBlock
{
    public function __construct(
        private readonly string $name,
        private readonly ?string $argument,
        public readonly int $fenceLength,
    ) {
        parent::__construct();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getArgument(): ?string
    {
        return $this->argument;
    }
}
