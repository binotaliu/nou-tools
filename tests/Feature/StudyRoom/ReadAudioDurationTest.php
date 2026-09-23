<?php

declare(strict_types=1);

use NouTools\Domains\StudyRoom\Actions\ReadAudioDuration;

it('reads the duration of an mp3 and an ogg file', function (string $file): void {
    expect(app(ReadAudioDuration::class)(base_path("tests/fixtures/audio/{$file}")))->toBe(2);
})->with(['silence-2s.mp3', 'silence-2s.ogg']);

it('returns null for a missing file', function (): void {
    expect(app(ReadAudioDuration::class)(base_path('tests/fixtures/audio/nope.mp3')))->toBeNull();
});

it('returns null for a file that is not audio', function (): void {
    $path = tempnam(sys_get_temp_dir(), 'not-audio');
    file_put_contents($path, 'definitely not audio');

    expect(app(ReadAudioDuration::class)($path))->toBeNull();

    unlink($path);
});
