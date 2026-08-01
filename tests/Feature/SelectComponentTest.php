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
        ->toContain('<option value="taiwan">台灣</option>');
});
