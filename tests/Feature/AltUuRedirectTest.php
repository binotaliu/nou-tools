<?php

it('redirects the old Alt UU page to its own portal', function (string $path) {
    $this->get($path)->assertRedirect('https://alt-uu.binota.org')->assertStatus(301);
})->with(['/alt-uu', '/alt-uu?embed']);
