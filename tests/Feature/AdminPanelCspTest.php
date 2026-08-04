<?php

use App\Enums\UserRole;
use App\Models\User;

use function Pest\Laravel\actingAs;

test('admin panel CSP header allows unsafe-inline styles and scripts without a neutralizing nonce', function () {
    $user = User::factory()->createOne([
        'roles' => [UserRole::Admin->value],
    ]);

    $response = actingAs($user)->get('/admin');

    $response->assertSuccessful();

    $csp = $response->headers->get('Content-Security-Policy');

    expect($csp)->not->toBeNull();

    $directives = collect(explode(';', $csp))
        ->mapWithKeys(function (string $directive) {
            $parts = explode(' ', trim($directive));

            return [array_shift($parts) => $parts];
        });

    // A nonce-source alongside 'unsafe-inline' would cause browsers to
    // ignore 'unsafe-inline' entirely, blocking Filament/Livewire's
    // vendor-rendered inline <style>/<script> tags (which carry no nonce).
    $hasNonce = fn (array $values) => collect($values)->contains(fn (string $value) => str_starts_with($value, "'nonce-"));

    expect($directives->get('style-src'))
        ->toContain("'unsafe-inline'");
    expect($hasNonce($directives->get('style-src')))->toBeFalse();

    expect($directives->get('script-src'))
        ->toContain("'unsafe-inline'")
        ->toContain("'unsafe-eval'");
    expect($hasNonce($directives->get('script-src')))->toBeFalse();
});
