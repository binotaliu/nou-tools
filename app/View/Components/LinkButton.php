<?php

namespace App\View\Components;

use Illuminate\View\View;

class LinkButton extends Button
{
    public function __construct(
        public string $href = '',
        public ?string $target = null,
        public ?string $rel = null,
        public bool $download = false,
        string $variant = 'primary',
        string $size = 'md',
        bool $disabled = false,
        bool $fullWidth = false,
        ?string $class = null,
    ) {
        parent::__construct(
            variant: $variant,
            size: $size,
            type: null,
            disabled: $disabled,
            fullWidth: $fullWidth,
            class: $class,
        );
    }

    protected function getPaddingClasses(): string
    {
        // Text links shouldn't have padding
        if (in_array($this->variant, ['link', 'text-link'])) {
            return '';
        }

        return parent::getPaddingClasses();
    }

    protected function getBaseClasses(): string
    {
        // Text links need different base classes
        if (in_array($this->variant, ['link', 'text-link'])) {
            return 'inline-flex items-center justify-center gap-2';
        }

        return parent::getBaseClasses();
    }

    public function render(): View
    {
        return view('components.link-button');
    }
}
