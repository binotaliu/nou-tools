<?php

declare(strict_types=1);

use App\Models\MusicPlaylist;
use App\Models\MusicTrack;

use function Pest\Laravel\get;

/**
 * @return array<string, string>
 */
$publicCspDirectives = function (): array {
    $response = get('/')->assertOk();

    $csp = (string) $response->headers->get('Content-Security-Policy');

    return collect(explode(';', $csp))
        ->mapWithKeys(function (string $directive): array {
            $parts = explode(' ', trim($directive), 2);

            return [$parts[0] => $parts[1] ?? ''];
        })
        ->all();
};

it('lets the study room play music audio and show playlist covers from their CDN origins', function () use ($publicCspDirectives) {
    config([
        'filesystems.disks.'.MusicTrack::AUDIO_DISK.'.disk' => 's3_audio',
        'filesystems.disks.'.MusicPlaylist::COVER_DISK.'.disk' => 's3_covers',
        'filesystems.disks.s3_audio' => [
            'driver' => 's3',
            'bucket' => 'music-audio',
            'region' => 'ap-northeast-1',
            'key' => 'key',
            'secret' => 'secret',
            'url' => 'https://audio.example-cdn.net',
        ],
        'filesystems.disks.s3_covers' => [
            'driver' => 's3',
            'bucket' => 'music-covers',
            'region' => 'ap-northeast-1',
            'key' => 'key',
            'secret' => 'secret',
            'url' => 'https://covers.example-cdn.net',
        ],
    ]);

    $directives = $publicCspDirectives();

    expect($directives['media-src'])->toContain("'self'")
        ->toContain('https://audio.example-cdn.net')
        ->and($directives['img-src'])->toContain('https://covers.example-cdn.net')
        ->and($directives['connect-src'])->not->toContain('example-cdn.net');
});

it('adds no music origins to the public CSP while files are stored locally', function () use ($publicCspDirectives) {
    config([
        'filesystems.disks.'.MusicTrack::AUDIO_DISK.'.disk' => 'public',
        'filesystems.disks.'.MusicPlaylist::COVER_DISK.'.disk' => 'public',
    ]);

    $directives = $publicCspDirectives();

    expect($directives['media-src'])->not->toContain('example-cdn.net')
        ->and($directives['img-src'])->not->toContain('example-cdn.net');
});

it('lets pages frame YouTube embeds from the privacy-enhanced domain only', function () use ($publicCspDirectives) {
    $frameSrc = explode(' ', $publicCspDirectives()['frame-src']);

    expect($frameSrc)
        ->toContain("'self'", 'https://www.youtube-nocookie.com')
        ->not->toContain('https://www.youtube.com');
});
