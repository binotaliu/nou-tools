<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\MusicTrack;
use App\Models\User;

final class MusicTrackPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, MusicTrack $musicTrack): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, MusicTrack $musicTrack): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, MusicTrack $musicTrack): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, MusicTrack $musicTrack): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, MusicTrack $musicTrack): bool
    {
        return $user->isAdmin();
    }
}
