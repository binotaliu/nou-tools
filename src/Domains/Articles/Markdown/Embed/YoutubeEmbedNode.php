<?php

declare(strict_types=1);

namespace NouTools\Domains\Articles\Markdown\Embed;

use League\CommonMark\Node\Block\AbstractBlock;

final class YoutubeEmbedNode extends AbstractBlock
{
    public function __construct(
        public readonly string $videoId,
        public readonly string $title,
        public readonly ?int $startSeconds,
    ) {
        parent::__construct();
    }
}
