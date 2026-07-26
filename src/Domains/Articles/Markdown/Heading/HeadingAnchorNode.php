<?php

declare(strict_types=1);

namespace NouTools\Domains\Articles\Markdown\Heading;

use League\CommonMark\Node\Inline\AbstractInline;

final class HeadingAnchorNode extends AbstractInline
{
    public function __construct(private readonly string $slug)
    {
        parent::__construct();
    }

    public function getSlug(): string
    {
        return $this->slug;
    }
}
