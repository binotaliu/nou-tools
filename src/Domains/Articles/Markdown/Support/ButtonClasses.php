<?php

declare(strict_types=1);

namespace NouTools\Domains\Articles\Markdown\Support;

/**
 * The Tailwind class list for a button, as used by the `:::cta` container.
 *
 * `CtaRenderer` needs this to style the anchors it emits into
 * server-rendered Markdown, where a Vue component can't reach.
 */
final readonly class ButtonClasses
{
    public function __construct(
        public string $variant = 'primary',
        public string $size = 'md',
        public bool $disabled = false,
        public bool $fullWidth = false,
        public ?string $class = null,
    ) {}

    public function toString(): string
    {
        return \implode(' ', \array_filter([
            $this->baseClasses(),
            $this->paddingClasses(),
            $this->variantClasses(),
            $this->fullWidth ? 'w-full' : '',
            $this->disabled ? 'opacity-50 cursor-not-allowed' : '',
            $this->class,
        ]));
    }

    private function baseClasses(): string
    {
        return 'inline-flex items-center justify-center gap-2 rounded-lg font-semibold transition';
    }

    private function paddingClasses(): string
    {
        return match ($this->size) {
            'sm' => 'px-3 py-1 text-sm',
            'lg' => 'px-6 py-3 text-lg',
            default => 'px-4 py-2',
        };
    }

    private function variantClasses(): string
    {
        return match ($this->variant) {
            'primary' => 'border border-warm-600 bg-warm-600 text-white hover:bg-warm-700 disabled:bg-warm-400',
            'secondary' => 'border border-warm-500 bg-white text-warm-900 hover:bg-warm-50 disabled:border-warm-200 disabled:bg-warm-50 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800 dark:disabled:border-zinc-700 dark:disabled:bg-zinc-950',
            'danger' => 'border border-red-100 bg-red-100 text-red-700 hover:bg-red-200 disabled:bg-red-50',
            'ghost' => 'border border-warm-200 bg-white text-warm-900 hover:bg-warm-50 disabled:border-warm-100 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800 dark:disabled:border-zinc-800',
            'warm-dark' => 'border border-warm-700 bg-warm-700 text-white hover:bg-warm-800 disabled:bg-warm-600',
            'warm-subtle' => 'border border-warm-200 bg-warm-200 text-warm-900 hover:bg-warm-300 disabled:bg-warm-100 dark:border-zinc-700 dark:bg-zinc-700 dark:text-zinc-100 dark:hover:bg-zinc-600 dark:disabled:bg-zinc-900',
            'link' => 'text-orange-600 hover:text-orange-700 underline underline-offset-4 hover:no-underline',
            'text-link' => 'text-orange-600 hover:text-orange-700',
            default => 'border border-orange-500 bg-orange-500 text-white hover:bg-orange-600 disabled:bg-orange-300',
        };
    }
}
