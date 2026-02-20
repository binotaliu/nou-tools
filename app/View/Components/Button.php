<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class Button extends Component
{
    public function __construct(
        public string $variant = 'primary',
        public string $size = 'md',
        public ?string $type = 'button',
        public bool $disabled = false,
        public bool $fullWidth = false,
        public ?string $class = null,
    ) {}

    protected function getBaseClasses(): string
    {
        return 'inline-flex items-center justify-center gap-2 rounded-lg font-semibold transition';
    }

    protected function getPaddingClasses(): string
    {
        return match ($this->size) {
            'sm' => 'px-3 py-1 text-sm',
            'lg' => 'px-6 py-3 text-lg',
            default => 'px-4 py-2',
        };
    }

    protected function getVariantClasses(): string
    {
        return match ($this->variant) {
            'primary' => 'bg-warm-600 text-white hover:bg-warm-700 disabled:bg-warm-400',
            'secondary' => 'border border-warm-500 bg-white text-warm-900 hover:bg-warm-50 disabled:border-warm-200 disabled:bg-warm-50',
            'danger' => 'bg-red-100 text-red-700 hover:bg-red-200 disabled:bg-red-50',
            'ghost' => 'border border-warm-200 bg-white text-warm-900 hover:bg-warm-50 disabled:border-warm-100',
            'warm-dark' => 'bg-warm-700 text-white hover:bg-warm-800 disabled:bg-warm-600',
            'warm-subtle' => 'bg-warm-200 text-warm-900 hover:bg-warm-300 disabled:bg-warm-100',
            'link' => 'text-orange-600 hover:text-orange-700 underline underline-offset-4',
            'text-link' => 'text-orange-600 hover:text-orange-700',
            default => 'bg-orange-500 text-white hover:bg-orange-600 disabled:bg-orange-300',
        };
    }

    protected function getWidthClasses(): string
    {
        return $this->fullWidth ? 'w-full' : '';
    }

    protected function getDisabledClasses(): string
    {
        return $this->disabled ? 'opacity-50 cursor-not-allowed' : '';
    }

    public function getClasses(): string
    {
        $classes = [
            $this->getBaseClasses(),
            $this->getPaddingClasses(),
            $this->getVariantClasses(),
            $this->getWidthClasses(),
            $this->getDisabledClasses(),
            $this->class,
        ];

        return implode(' ', array_filter($classes));
    }

    public function render(): View
    {
        return view('components.button');
    }
}
