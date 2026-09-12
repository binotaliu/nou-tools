<?php

declare(strict_types=1);

namespace App\Csp;

use Spatie\Csp\Directive;
use Spatie\Csp\Policy;
use Spatie\Csp\Preset;
use Spatie\Csp\Presets\Basic;
use Spatie\Csp\Presets\CloudflareTurnstile;
use Spatie\Csp\Presets\CloudflareWebAnalytics;
use Spatie\Csp\Presets\GoogleAnalytics;
use Spatie\Csp\Presets\GoogleTagManager;

final class PublicSitePolicy implements Preset
{
    public function configure(Policy $policy): void
    {
        (new Basic)->configure($policy);
        (new GoogleTagManager)->configure($policy);
        (new GoogleAnalytics)->configure($policy);
        (new CloudflareTurnstile)->configure($policy);
        (new CloudflareWebAnalytics)->configure($policy);

        $policy->add(Directive::IMG, '*.tile.openstreetmap.org');

        // Twemoji: script from jsDelivr, emoji images fetched from jsDelivr's default CDN base.
        $policy->add(Directive::SCRIPT, 'cdn.jsdelivr.net');
        $policy->add(Directive::IMG, 'cdn.jsdelivr.net');

        $reverbOrigins = $this->reverbConnectSources();

        if ($reverbOrigins !== []) {
            $policy->add(Directive::CONNECT, $reverbOrigins);
        }
    }

    /**
     * Allow the browser to reach Reverb over both WebSocket and HTTP.
     *
     * pusher-js falls back to XHR streaming on the same host/port when a
     * WebSocket connection cannot be established, so both origins must be
     * allowed or the fallback would be blocked by CSP. Reads server-side
     * config only (never VITE_* env vars), since those are baked into the
     * built JS bundle rather than evaluated at request time.
     *
     * @return array<int, string>
     */
    private function reverbConnectSources(): array
    {
        $options = config('broadcasting.connections.reverb.options');

        $host = $options['host'] ?? null;

        if (empty($host)) {
            return [];
        }

        $port = $options['port'] ?? null;
        $scheme = $options['scheme'] ?? 'https';

        $hostAndPort = "{$host}:{$port}";

        if ($scheme === 'https') {
            return ["wss://{$hostAndPort}", "https://{$hostAndPort}"];
        }

        return ["ws://{$hostAndPort}", "http://{$hostAndPort}"];
    }
}
