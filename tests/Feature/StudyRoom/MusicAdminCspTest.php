<?php

declare(strict_types=1);

use App\Models\MusicPlaylist;
use App\Models\MusicTrack;
use App\Models\NewsletterIssue;
use App\Models\User;

use function Pest\Laravel\actingAs;

/**
 * @return array<string, string>
 */
function adminCspDirectives(): array
{
    $csp = (string) actingAs(User::factory()->createOne())
        ->get(route('filament.admin.auth.profile'))
        ->headers->get('Content-Security-Policy');

    return collect(explode(';', $csp))
        ->mapWithKeys(function (string $directive): array {
            $parts = explode(' ', trim($directive), 2);

            return [$parts[0] => $parts[1] ?? ''];
        })
        ->all();
}

it('lets the admin panel reach the S3 buckets holding music audio and playlist covers', function () {
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

    $directives = adminCspDirectives();

    expect($directives['connect-src'])
        ->toContain('https://music-audio.s3.ap-northeast-1.amazonaws.com')
        ->toContain('https://music-covers.s3.ap-northeast-1.amazonaws.com')
        ->and($directives['img-src'])->toContain('https://covers.example-cdn.net');
});

it('adds no music origins to the admin CSP while files are stored locally', function () {
    config([
        'filesystems.disks.'.NewsletterIssue::COVER_DISK.'.disk' => 'public',
        'filesystems.disks.'.MusicTrack::AUDIO_DISK.'.disk' => 'public',
        'filesystems.disks.'.MusicPlaylist::COVER_DISK.'.disk' => 'public',
    ]);

    $directives = adminCspDirectives();

    expect($directives['connect-src'])->not->toContain('amazonaws.com')
        ->and($directives['img-src'])->not->toContain('example-cdn.net');
});

it('lets the admin panel play a just-picked audio file from its blob: preview', function () {
    expect(adminCspDirectives()['media-src'])->toContain('blob:');
});
