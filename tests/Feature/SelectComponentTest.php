<?php

use Illuminate\Support\Facades\Blade;

it('renders the shared select component with the application input style', function () {
    $html = Blade::render(
        '<x-select id="country" name="country">'.
        '<option value="">請選擇</option>'.
        '<option value="taiwan">台灣</option>'.
        '</x-select>'
    );

    expect($html)
        ->toContain('<select')
        ->toContain('id="country"')
        ->toContain('name="country"')
        ->toContain('w-full rounded-lg border border-warm-200 dark:border-zinc-700 px-3 py-2 text-sm focus:border-orange-300 focus:ring-orange-300')
        ->toContain('<option value="taiwan">台灣</option>');
});
