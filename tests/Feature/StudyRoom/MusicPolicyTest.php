<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\MusicPlaylist;
use App\Models\MusicTrack;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

it('lets only admins manage music tracks and playlists', function (string $model): void {
    $record = $model::factory()->create();
    $admin = User::factory()->create(['roles' => [UserRole::Admin->value]]);
    $member = User::factory()->create();

    foreach (['viewAny', 'create'] as $ability) {
        expect(Gate::forUser($admin)->allows($ability, $model))->toBeTrue()
            ->and(Gate::forUser($member)->allows($ability, $model))->toBeFalse();
    }

    foreach (['view', 'update', 'delete'] as $ability) {
        expect(Gate::forUser($admin)->allows($ability, $record))->toBeTrue()
            ->and(Gate::forUser($member)->allows($ability, $record))->toBeFalse();
    }
})->with([MusicTrack::class, MusicPlaylist::class]);
