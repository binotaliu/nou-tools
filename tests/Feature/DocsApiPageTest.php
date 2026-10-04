<?php

it('serves the API docs with a CSP that lets Redoc inject its styles', function () {
    $csp = $this->get(route('docs.api.view'))
        ->assertOk()
        ->assertSee(route('docs.api.yaml'), false)
        ->headers->get('Content-Security-Policy');

    // Redoc's styled-components cannot carry a nonce, and a nonce would make
    // browsers ignore 'unsafe-inline'; scripts keep theirs.
    expect($csp)
        ->toContain("style-src 'self' 'unsafe-inline'")
        ->toContain('cdn.redoc.ly')
        ->toContain("worker-src 'self' blob:")
        ->and(preg_match('/style-src[^;]*nonce-/', $csp))->toBe(0)
        ->and(preg_match('/script-src[^;]*nonce-/', $csp))->toBe(1);
});
