<?php

declare(strict_types=1);

namespace NouTools\Domains\Articles\Markdown\Container;

final class ContainerRendererRegistry
{
    /** @var array<string, ContainerRendererInterface> */
    private array $renderers = [];

    public function register(string $name, ContainerRendererInterface $renderer): self
    {
        $this->renderers[$name] = $renderer;

        return $this;
    }

    public function has(string $name): bool
    {
        return isset($this->renderers[$name]);
    }

    public function get(string $name): ?ContainerRendererInterface
    {
        return $this->renderers[$name] ?? null;
    }
}
