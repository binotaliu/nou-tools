<?php

it('returns a successful response', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});

it('uses the standard header logo on error page', function () {
    $response = $this->get('/ThisRouteDoesNotExist');

    $response->assertStatus(404);
    $response->assertSee('NOU 小幫手');
    // verify we have an SVG icon in the header (the standard book-open icon renders as SVG)
    $response->assertSee('<svg', false);
});
