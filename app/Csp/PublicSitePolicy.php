<?php

declare(strict_types=1);

namespace App\Csp;

use App\Models\MusicPlaylist;
use App\Models\MusicTrack;
use App\Models\NewsletterIssue;
use NouTools\Domains\Articles\Markdown\Embed\YoutubeEmbedProcessor;
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

        // Twemoji script is bundled via npm (@twemoji/api); emoji image assets
        // are still fetched from jsDelivr's default CDN base at runtime, since
        // nicknames can contain arbitrary emoji beyond our fixed seat-choice list.
        $policy->add(Directive::IMG, 'cdn.jsdelivr.net');

        // Markdown (articles, newsletter) turns a pasted YouTube embed into an
        // iframe on the privacy-enhanced domain; nothing else may be framed.
        $policy->add(Directive::FRAME, YoutubeEmbedProcessor::EMBED_ORIGIN);

        if (($coverOrigin = NewsletterIssue::coverImageOrigin()) !== null) {
            $policy->add(Directive::IMG, $coverOrigin);
        }

        // The study room's cassette player streams tracks through an <audio>
        // element (media-src) and shows playlist covers (img-src). Both stay
        // same-origin while the disks are local, so this only adds the
        // CDN origin once they move to S3.
        if (($audioOrigin = ScopedDiskOrigins::serving(MusicTrack::AUDIO_DISK)) !== null) {
            $policy->add(Directive::MEDIA, $audioOrigin);
        }

        if (($musicCoverOrigin = ScopedDiskOrigins::serving(MusicPlaylist::COVER_DISK)) !== null) {
            $policy->add(Directive::IMG, $musicCoverOrigin);
        }

        // The og-image card (spatie/laravel-og-image) loads Noto Sans TC from
        // Google Fonts, but only in the `?ogimage` screenshot document, so the
        // rest of the site's CSP stays untouched.
        if (request()->has(config('og-image.preview_parameter', 'ogimage'))) {
            $policy->add(Directive::STYLE, 'https://fonts.googleapis.com');
            $policy->add(Directive::FONT, 'https://fonts.gstatic.com');
        }

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
