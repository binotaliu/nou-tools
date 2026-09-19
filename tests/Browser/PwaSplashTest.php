<?php

// The launch splash is removed once Vue mounts, so a fresh #app-splash is
// injected to read the stylesheet's verdict: hidden in a normal browser tab,
// shown once the head script's html[data-pwa] flag is set.

function splashDisplay($page): string
{
    return $page->script(<<<'JS'
        (() => {
            document.getElementById('app-splash')?.remove()
            const splash = document.createElement('div')
            splash.id = 'app-splash'
            document.body.append(splash)
            return getComputedStyle(splash).display
        })()
    JS);
}

it('keeps the launch splash hidden in a normal browser tab', function () {
    $page = visit('/announcements')->assertSee('學校公告');

    expect(splashDisplay($page))->toBe('none');
});

it('shows the launch splash when running as an installed PWA', function () {
    $page = visit('/announcements')->assertSee('學校公告');

    $page->script("document.documentElement.dataset.pwa = ''");

    expect(splashDisplay($page))->toBe('flex');
});
