<?php

// The skip-to-main link lives in AppLayout.vue (resources/js/Layouts/AppLayout.vue),
// so it's only present in the client-rendered DOM after Vue hydrates, not in
// the server-rendered HTML a Feature test sees.

it('renders skip-to-main link on pages', function () {
    $page = visit('/');

    $page->assertNoJavaScriptErrors()
        ->assertPresent('a[href="#main-content"]')
        ->assertSee('跳到主要區塊');
});
