<?php

test('homepage returns Link headers for API discovery', function () {
    $response = $this->get(route('home'));

    $response->assertStatus(200)
        ->assertHeader('Link', implode(', ', [
            '<'.route('docs.api.view').'>; rel="service-doc"',
            '<'.route('docs.api.yaml').'>; rel="service-desc"',
        ]));
});
