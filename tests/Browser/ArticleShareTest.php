<?php

// The article page's share button was extracted into the shared ShareButton
// component (also used by the newsletter); this keeps the article's own test
// ids and copy-link fallback working.
it('falls back to a copy-link dialog on articles when the browser cannot share', function () {
    $page = visit('/kb/about-nou');

    $page->script('Object.defineProperty(navigator, "share", { value: undefined, configurable: true })');

    $page->click('[data-testid="article-share-button"]')
        ->assertPresent('[data-testid="article-share-modal"]')
        ->assertValue('[data-testid="article-share-modal"] input', url('/kb/about-nou'))
        ->click('[data-testid="article-share-close"]')
        ->assertNotPresent('[data-testid="article-share-modal"]');
});
