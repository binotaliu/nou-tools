<?php

it('allows the reverb websocket and http origins in connect-src when reverb is configured', function () {
    config()->set('broadcasting.connections.reverb.options', [
        'host' => 'realtime.example.com',
        'port' => 443,
        'scheme' => 'https',
    ]);

    $response = $this->get('/');

    $response->assertStatus(200);

    $csp = $response->headers->get('Content-Security-Policy');
    $connectSrc = collect(explode(';', $csp))
        ->first(fn (string $directive) => str_contains($directive, 'connect-src'));

    expect($connectSrc)
        ->toContain('wss://realtime.example.com:443')
        ->toContain('https://realtime.example.com:443');
});

it('adds no reverb origins to connect-src when reverb has no host configured', function (?string $host) {
    config()->set('broadcasting.connections.reverb.options', [
        'host' => $host,
        'port' => 443,
        'scheme' => 'https',
    ]);

    $response = $this->get('/');

    $response->assertStatus(200);

    $csp = $response->headers->get('Content-Security-Policy');
    $connectSrc = collect(explode(';', $csp))
        ->first(fn (string $directive) => str_contains($directive, 'connect-src'));

    expect($connectSrc)
        ->not->toContain('wss://')
        ->not->toContain('realtime.example.com');
})->with([
    'empty string' => [''],
    'null' => [null],
]);

it('allows cdn.jsdelivr.net in img-src for twemoji images, but not script-src', function () {
    $response = $this->get('/');

    $response->assertStatus(200);

    $csp = $response->headers->get('Content-Security-Policy');
    $directives = collect(explode(';', $csp));

    $scriptSrc = $directives->first(fn (string $directive) => str_contains($directive, 'script-src'));
    $imgSrc = $directives->first(fn (string $directive) => str_contains($directive, 'img-src'));

    expect($imgSrc)->toContain('cdn.jsdelivr.net');
    expect($scriptSrc)->not->toContain('cdn.jsdelivr.net');
});
