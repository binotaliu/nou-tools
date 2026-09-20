<?php

declare(strict_types=1);

namespace NouTools\Domains\Articles\Markdown\Embed;

use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;

final class YoutubeEmbedRenderer implements NodeRendererInterface
{
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): \Stringable
    {
        if (! $node instanceof YoutubeEmbedNode) {
            throw new \InvalidArgumentException('Incompatible node type: '.$node::class);
        }

        $src = YoutubeEmbedProcessor::EMBED_ORIGIN.'/embed/'.$node->videoId
            .($node->startSeconds !== null ? '?start='.$node->startSeconds : '');

        $iframe = new HtmlElement('iframe', [
            'src' => $src,
            'title' => $node->title,
            'loading' => 'lazy',
            'allow' => 'accelerometer; encrypted-media; gyroscope; picture-in-picture; web-share',
            'referrerpolicy' => 'strict-origin-when-cross-origin',
            'allowfullscreen' => true,
        ]);

        return new HtmlElement('div', ['class' => 'md-video'], $iframe);
    }
}
