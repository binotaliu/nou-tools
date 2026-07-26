<?php

declare(strict_types=1);

namespace NouTools\Domains\Articles\Markdown\Container;

use League\CommonMark\Node\Block\AbstractBlock;
use League\CommonMark\Parser\Block\AbstractBlockContinueParser;
use League\CommonMark\Parser\Block\BlockContinue;
use League\CommonMark\Parser\Block\BlockContinueParserInterface;
use League\CommonMark\Parser\Cursor;

final class ContainerParser extends AbstractBlockContinueParser
{
    private readonly ContainerNode $block;

    public function __construct(string $name, ?string $argument, int $fenceLength)
    {
        $this->block = new ContainerNode($name, $argument, $fenceLength);
    }

    public function getBlock(): ContainerNode
    {
        return $this->block;
    }

    public function isContainer(): bool
    {
        return true;
    }

    public function canContain(AbstractBlock $childBlock): bool
    {
        return true;
    }

    public function tryContinue(Cursor $cursor, BlockContinueParserInterface $activeBlockParser): ?BlockContinue
    {
        if (! $cursor->isIndented() && $this->isClosingFence($cursor)) {
            return BlockContinue::finished();
        }

        return BlockContinue::at($cursor);
    }

    private function isClosingFence(Cursor $cursor): bool
    {
        $remainder = \trim($cursor->getRemainder());

        if ($remainder === '' || ! \preg_match('/^:+$/', $remainder)) {
            return false;
        }

        return \strlen($remainder) >= $this->block->fenceLength;
    }
}
