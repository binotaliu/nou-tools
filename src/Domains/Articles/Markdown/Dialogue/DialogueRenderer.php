<?php

declare(strict_types=1);

namespace NouTools\Domains\Articles\Markdown\Dialogue;

use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Util\HtmlElement;
use League\CommonMark\Util\Xml;
use NouTools\Domains\Articles\Markdown\Container\ContainerNode;
use NouTools\Domains\Articles\Markdown\Container\ContainerRendererInterface;

final readonly class DialogueRenderer implements ContainerRendererInterface
{
    public function __construct(private PersonaResolver $personaResolver) {}

    public function render(ContainerNode $node, ChildNodeRendererInterface $childRenderer): \Stringable
    {
        $parts = [];

        foreach ($node->children() as $child) {
            $parts[] = $child instanceof DialogueTurnNode
                ? $this->renderTurn($child, $childRenderer)
                : $childRenderer->renderNodes([$child]);
        }

        return new HtmlElement('div', [
            'class' => 'md-dialogue',
            'role' => 'group',
            'aria-label' => '對話',
        ], $parts);
    }

    private function renderTurn(DialogueTurnNode $turn, ChildNodeRendererInterface $childRenderer): HtmlElement
    {
        $persona = $this->personaResolver->resolve($turn->getSpeaker());
        $mood = $turn->getMood();

        $avatarAttributes = ['class' => 'md-dialogue-avatar', 'aria-hidden' => 'true'];
        if ($mood !== null) {
            $avatarAttributes['data-mood'] = $mood;
        }

        $avatarContents = $persona->image !== null
            ? new HtmlElement('img', [
                'class' => 'md-dialogue-avatar-img',
                'src' => $persona->image,
                'alt' => '',
                'loading' => 'lazy',
            ], '', true)
            : Xml::escape((string) $persona->moodEmoji($mood));

        $avatar = new HtmlElement('div', $avatarAttributes, $avatarContents);

        $speaker = new HtmlElement('p', ['class' => 'md-dialogue-speaker'], Xml::escape($turn->getSpeaker()));
        $body = new HtmlElement('div', ['class' => 'md-dialogue-body'], $childRenderer->renderNodes($turn->children()));
        $bubble = new HtmlElement('div', ['class' => 'md-dialogue-bubble'], [$speaker, $body]);

        return new HtmlElement('div', [
            'class' => 'md-dialogue-turn',
            'data-persona' => $persona->slug,
        ], [$avatar, $bubble]);
    }
}
