<?php

declare(strict_types=1);

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\TextInput;
use Illuminate\Support\HtmlString;

/**
 * A date field shown and typed as `YYYY-MM-DD`. Filament's own picker can't do
 * both: the native input follows the browser's locale, and the JS picker's
 * text box is read-only. The state is the same `Y-m-d` string either way, and
 * a calendar button opens the browser's picker to fill it in.
 */
final class IsoDateInput extends TextInput
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->placeholder('YYYY-MM-DD')
            ->mask('9999-99-99')
            ->inputMode('numeric')
            ->autocomplete(false)
            ->rule('date_format:Y-m-d')
            ->suffix(fn (self $component): HtmlString => new HtmlString(
                view('filament.forms.iso-date-picker-button', [
                    'statePath' => $component->getStatePath(),
                    'disabled' => $component->isDisabled(),
                ])->render()
            ));
    }
}
